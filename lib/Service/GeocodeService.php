<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\App\IAppManager;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Geocodes the physical address of a project owned by a given organization.
 * Persists positive AND negative results in adminpage_geocode_cache, so we
 * never send the same address to Nominatim twice (per the OSMF usage policy).
 */
class GeocodeService {

    private const NOMINATIM_URL = 'https://nominatim.openstreetmap.org/search';
    private const HTTP_TIMEOUT_SECONDS = 10;

    /** Mirrors DeckService::statusLabel — kept local so this service stays free of inter-service deps. */
    private const STATUS_LABELS = [
        0 => 'Active',
        1 => 'Waiting on Customer',
        2 => 'On Hold',
    ];

    private const MAX_FRESH_GEOCODES_PER_REQUEST = 10;
    private const NOMINATIM_SPACING_USEC = 1_100_000; // 1.1s between requests

    private IDBConnection $db;
    private IClientService $clientService;
    private IAppManager $appManager;
    private IConfig $config;
    private LoggerInterface $logger;

    public function __construct(
        IDBConnection $db,
        IClientService $clientService,
        IAppManager $appManager,
        IConfig $config,
        LoggerInterface $logger
    ) {
        $this->db = $db;
        $this->clientService = $clientService;
        $this->appManager = $appManager;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @return array Status-tagged result:
     *   ['status' => 'no_org_match']
     *   ['status' => 'no_address']
     *   ['status' => 'not_found',  'addrHash' => string, 'fromCache' => bool]
     *   ['status' => 'unavailable']
     *   ['status' => 'ok', 'lat' => float, 'lng' => float, 'displayName' => ?string,
     *                      'source' => string, 'addrHash' => string, 'fromCache' => bool]
     */
    public function geocodeProject(int $orgId, int $projectId): array {
        $sql = "SELECT loc_street, loc_city, loc_zip
                FROM *PREFIX*custom_projects
                WHERE id = ? AND organization_id = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $projectId, \PDO::PARAM_INT);
        $stmt->bindValue(2, $orgId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if (!$row) {
            return ['status' => 'no_org_match'];
        }

        $street = trim((string)($row['loc_street'] ?? ''));
        $city   = trim((string)($row['loc_city']   ?? ''));
        $zip    = trim((string)($row['loc_zip']    ?? ''));

        if ($street === '' && $city === '' && $zip === '') {
            return ['status' => 'no_address'];
        }

        $addrHash = $this->hashAddress($street, $city, $zip);

        $cached = $this->lookupCache($addrHash);
        if ($cached !== null) {
            if ($cached['lat'] === null || $cached['lng'] === null) {
                return [
                    'status'    => 'not_found',
                    'addrHash'  => $addrHash,
                    'fromCache' => true,
                ];
            }
            return [
                'status'      => 'ok',
                'lat'         => (float)$cached['lat'],
                'lng'         => (float)$cached['lng'],
                'displayName' => $cached['display_name'],
                'source'      => $cached['source'],
                'addrHash'    => $addrHash,
                'fromCache'   => true,
            ];
        }

        $query = $this->buildQueryString($street, $city, $zip);
        $hit = $this->callNominatim($query);

        if ($hit === null) {
            // Transient failure — don't cache; let the next click retry.
            return ['status' => 'unavailable'];
        }

        if ($hit === []) {
            // Nominatim returned no match — cache a negative entry.
            $this->insertCache($addrHash, null, null, null, 'nominatim');
            return [
                'status'    => 'not_found',
                'addrHash'  => $addrHash,
                'fromCache' => false,
            ];
        }

        $lat = (float)$hit['lat'];
        $lng = (float)$hit['lng'];
        $displayName = $hit['display_name'] ?? null;

        $this->insertCache($addrHash, $lat, $lng, $displayName, 'nominatim');

        return [
            'status'      => 'ok',
            'lat'         => $lat,
            'lng'         => $lng,
            'displayName' => $displayName,
            'source'      => 'nominatim',
            'addrHash'    => $addrHash,
            'fromCache'   => false,
        ];
    }

    /**
     * @return array|null  cached row, or null if no row
     */
    private function lookupCache(string $addrHash): ?array {
        $stmt = $this->db->prepare(
            "SELECT lat, lng, display_name, source
             FROM *PREFIX*adminpage_geocode_cache
             WHERE addr_hash = ? LIMIT 1"
        );
        $stmt->bindValue(1, $addrHash, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function insertCache(
        string $addrHash,
        ?float $lat,
        ?float $lng,
        ?string $displayName,
        string $source
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO *PREFIX*adminpage_geocode_cache
             (addr_hash, lat, lng, display_name, source, created_at)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->bindValue(1, $addrHash, \PDO::PARAM_STR);
        if ($lat === null) {
            $stmt->bindValue(2, null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(2, number_format($lat, 7, '.', ''), \PDO::PARAM_STR);
        }
        if ($lng === null) {
            $stmt->bindValue(3, null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(3, number_format($lng, 7, '.', ''), \PDO::PARAM_STR);
        }
        if ($displayName === null) {
            $stmt->bindValue(4, null, \PDO::PARAM_NULL);
        } else {
            $stmt->bindValue(4, mb_substr($displayName, 0, 255), \PDO::PARAM_STR);
        }
        $stmt->bindValue(5, $source, \PDO::PARAM_STR);
        $stmt->bindValue(6, time(), \PDO::PARAM_INT);
        $stmt->execute();
    }

    private function buildQueryString(string $street, string $city, string $zip): string {
        $parts = array_filter([$street, $zip, $city], static fn($p) => $p !== '');
        return implode(', ', $parts);
    }

    private function hashAddress(?string $street, ?string $city, ?string $zip): string {
        $normalized = strtolower(trim((string)$street))
            . '|' . strtolower(trim((string)$city))
            . '|' . strtolower(trim((string)$zip));
        return hash('sha256', $normalized);
    }

    /**
     * @return array|null
     *   - associative ['lat'=>..., 'lng'=>..., 'display_name'=>...] on a hit
     *   - [] (empty array) when Nominatim returned no match
     *   - null on transient failure (timeout / non-2xx / unparseable response)
     */
    protected function callNominatim(string $query) {
        $userAgent = sprintf(
            'Nextcloud-AdminPage/%s (%s)',
            $this->appManager->getAppVersion('adminpage'),
            $this->resolveInstanceHost()
        );
        try {
            $client = $this->clientService->newClient();
            $response = $client->get(self::NOMINATIM_URL, [
                'query' => [
                    'format' => 'jsonv2',
                    'limit'  => 1,
                    'q'      => $query,
                ],
                'headers' => [
                    'User-Agent' => $userAgent,
                    'Accept'     => 'application/json',
                ],
                'timeout' => self::HTTP_TIMEOUT_SECONDS,
            ]);
        } catch (\Throwable $e) {
            $this->logger->warning('Nominatim request failed', [
                'app' => 'adminpage',
                'exception' => $e,
            ]);
            return null;
        }

        if ($response->getStatusCode() !== 200) {
            $this->logger->warning('Nominatim non-200', [
                'app'    => 'adminpage',
                'status' => $response->getStatusCode(),
            ]);
            return null;
        }

        $body = (string)$response->getBody();
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return null;
        }
        if (empty($decoded)) {
            return [];
        }
        $first = $decoded[0];
        if (!isset($first['lat'], $first['lon'])) {
            return [];
        }
        return [
            'lat'          => $first['lat'],
            'lng'          => $first['lon'],
            'display_name' => $first['display_name'] ?? null,
        ];
    }

    private function resolveInstanceHost(): string {
        $cli = (string)$this->config->getSystemValue('overwrite.cli.url', '');
        if ($cli !== '') {
            $host = parse_url($cli, PHP_URL_HOST);
            if (is_string($host) && $host !== '') {
                return $host;
            }
        }
        $trusted = $this->config->getSystemValue('trusted_domains', []);
        if (is_array($trusted) && isset($trusted[0]) && is_string($trusted[0]) && $trusted[0] !== '') {
            return $trusted[0];
        }
        return 'unknown-host';
    }

    /**
     * Geocodes every project in an organization, reusing the address cache.
     * On-demand geocoding is capped to MAX_FRESH_GEOCODES_PER_REQUEST per call,
     * with NOMINATIM_SPACING_USEC sleep between successive Nominatim hits.
     * Anything beyond the cap is returned with geocodeStatus='pending' and will
     * be resolved on a subsequent call.
     */
    public function geocodeOrgProjects(int $orgId): array {
        $sql = "
            SELECT cp.id, cp.name, cp.number, cp.status,
                   cp.loc_street, cp.loc_city, cp.loc_zip,
                   cp.board_id
            FROM *PREFIX*custom_projects cp
            WHERE cp.organization_id = ?
            ORDER BY cp.id
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $orgId, \PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();
        if (empty($rows)) {
            return ['projects' => [], 'geocodingInFlight' => 0];
        }

        $boardIds = [];
        foreach ($rows as $r) {
            $bid = (int)$r['board_id'];
            if ($bid > 0) {
                $boardIds[] = $bid;
            }
        }
        $boardIds = array_values(array_unique($boardIds));
        $taskStatsByBoard = $this->fetchTaskStatsForBoards($boardIds);
        $assigneesByBoard = $this->fetchAssigneesForBoards($boardIds);

        $hashes = [];
        $hashSet = [];
        foreach ($rows as $r) {
            $street = trim((string)($r['loc_street'] ?? ''));
            $city   = trim((string)($r['loc_city']   ?? ''));
            $zip    = trim((string)($r['loc_zip']    ?? ''));
            if ($street === '' && $city === '' && $zip === '') {
                $hashes[(int)$r['id']] = null;
                continue;
            }
            $h = $this->hashAddress($street, $city, $zip);
            $hashes[(int)$r['id']] = $h;
            $hashSet[$h] = true;
        }
        $cache = $this->lookupCacheBatch(array_keys($hashSet));

        $projects = [];
        $missQueue = [];
        foreach ($rows as $r) {
            $pid = (int)$r['id'];
            $bid = (int)$r['board_id'];
            $status = (int)$r['status'];
            $stats = $taskStatsByBoard[$bid] ?? ['totalTasks' => 0, 'doneTasks' => 0, 'overdueTasks' => 0];
            $completion = $stats['totalTasks'] > 0
                ? (int)round($stats['doneTasks'] / $stats['totalTasks'] * 100)
                : 0;
            $project = [
                'id'            => $pid,
                'name'          => (string)$r['name'],
                'number'        => (string)($r['number'] ?? ''),
                'status'        => $status,
                'statusLabel'   => self::STATUS_LABELS[$status] ?? 'Active',
                'completionPct' => $completion,
                'totalTasks'    => (int)$stats['totalTasks'],
                'doneTasks'    => (int)$stats['doneTasks'],
                'overdueTasks'  => (int)$stats['overdueTasks'],
                'assignees'     => $assigneesByBoard[$bid] ?? [],
                'lat'           => null,
                'lng'           => null,
                'displayName'   => null,
                'geocodeStatus' => 'pending',
            ];

            $h = $hashes[$pid];
            if ($h === null) {
                $project['geocodeStatus'] = 'no_address';
                $projects[] = $project;
                continue;
            }
            if (isset($cache[$h])) {
                $row = $cache[$h];
                if ($row['lat'] === null || $row['lng'] === null) {
                    $project['geocodeStatus'] = 'not_found';
                } else {
                    $project['lat']         = (float)$row['lat'];
                    $project['lng']         = (float)$row['lng'];
                    $project['displayName'] = $row['display_name'];
                    $project['geocodeStatus'] = 'ok';
                }
                $projects[] = $project;
                continue;
            }

            $missQueue[count($projects)] = [
                'hash'   => $h,
                'street' => trim((string)($r['loc_street'] ?? '')),
                'city'   => trim((string)($r['loc_city']   ?? '')),
                'zip'    => trim((string)($r['loc_zip']    ?? '')),
            ];
            $projects[] = $project;
        }

        $processed = 0;
        $first = true;
        foreach ($missQueue as $idx => $miss) {
            if ($processed >= self::MAX_FRESH_GEOCODES_PER_REQUEST) {
                break;
            }
            if (!$first) {
                usleep(self::NOMINATIM_SPACING_USEC);
            }
            $first = false;

            $q = $this->buildQueryString($miss['street'], $miss['city'], $miss['zip']);
            $hit = $this->callNominatim($q);
            $processed++;

            if ($hit === null) {
                continue;
            }
            if ($hit === []) {
                $this->insertCache($miss['hash'], null, null, null, 'nominatim');
                $projects[$idx]['geocodeStatus'] = 'not_found';
                continue;
            }
            $lat = (float)$hit['lat'];
            $lng = (float)$hit['lng'];
            $displayName = $hit['display_name'] ?? null;
            $this->insertCache($miss['hash'], $lat, $lng, $displayName, 'nominatim');
            $projects[$idx]['lat']           = $lat;
            $projects[$idx]['lng']           = $lng;
            $projects[$idx]['displayName']   = $displayName;
            $projects[$idx]['geocodeStatus'] = 'ok';
        }

        $pending = 0;
        foreach ($projects as $p) {
            if ($p['geocodeStatus'] === 'pending') {
                $pending++;
            }
        }
        return ['projects' => $projects, 'geocodingInFlight' => $pending];
    }

    /**
     * @param string[] $hashes
     * @return array<string, array> hash => row{lat,lng,display_name,source}
     */
    private function lookupCacheBatch(array $hashes): array {
        if (empty($hashes)) return [];
        $placeholders = implode(',', array_fill(0, count($hashes), '?'));
        $stmt = $this->db->prepare(
            "SELECT addr_hash, lat, lng, display_name, source
             FROM *PREFIX*adminpage_geocode_cache
             WHERE addr_hash IN ({$placeholders})"
        );
        foreach ($hashes as $i => $h) {
            $stmt->bindValue($i + 1, $h, \PDO::PARAM_STR);
        }
        $stmt->execute();
        $map = [];
        foreach ($stmt->fetchAll() as $row) {
            $map[(string)$row['addr_hash']] = $row;
        }
        return $map;
    }

    /**
     * @param int[] $boardIds
     * @return array<int, array{totalTasks: int, doneTasks: int, overdueTasks: int}>
     */
    private function fetchTaskStatsForBoards(array $boardIds): array {
        if (empty($boardIds)) return [];
        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));
        $sql = "
            SELECT b.id AS board_id,
                   COUNT(c.id) AS total,
                   SUM(CASE WHEN s.title = 'Approved/Done' THEN 1 ELSE 0 END) AS done,
                   SUM(CASE WHEN c.duedate IS NOT NULL AND DATE(c.duedate) < CURDATE()
                              AND s.title <> 'Approved/Done' THEN 1 ELSE 0 END) AS overdue
            FROM *PREFIX*deck_boards b
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            WHERE b.id IN ({$placeholders}) AND b.deleted_at = 0
            GROUP BY b.id
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($boardIds as $i => $bid) {
            $stmt->bindValue($i + 1, $bid, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $map[(int)$r['board_id']] = [
                'totalTasks'   => (int)$r['total'],
                'doneTasks'    => (int)$r['done'],
                'overdueTasks' => (int)$r['overdue'],
            ];
        }
        return $map;
    }

    /**
     * @param int[] $boardIds
     * @return array<int, string[]>
     */
    private function fetchAssigneesForBoards(array $boardIds): array {
        if (empty($boardIds)) return [];
        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));
        $sql = "
            SELECT DISTINCT b.id AS board_id, au.participant
            FROM *PREFIX*deck_boards b
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            JOIN *PREFIX*deck_cards c  ON c.stack_id = s.id AND c.deleted_at = 0
            JOIN *PREFIX*deck_assigned_users au ON au.card_id = c.id
            WHERE b.id IN ({$placeholders})
              AND b.deleted_at = 0
              AND au.type = 0
        ";
        $stmt = $this->db->prepare($sql);
        foreach ($boardIds as $i => $bid) {
            $stmt->bindValue($i + 1, $bid, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $map = [];
        foreach ($stmt->fetchAll() as $r) {
            $bid = (int)$r['board_id'];
            if (!isset($map[$bid])) $map[$bid] = [];
            $map[$bid][] = (string)$r['participant'];
        }
        return $map;
    }
}
