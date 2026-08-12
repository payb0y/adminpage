<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\Files\IRootFolder;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IServerContainer;

use OCA\Organization\Service\UserQuotaService;

use Psr\Log\LoggerInterface;

final class StorageMonitoringService {
    private const CACHE_TTL = 300;

    public function __construct(
        private IDBConnection $db,
        private IRootFolder $rootFolder,
        private ICacheFactory $cacheFactory,
        private IServerContainer $serverContainer,
        private UserQuotaService $userQuotaService,
        private LoggerInterface $logger,
    ) {
    }

    public function getOrganizationStorage(int $organizationId, bool $refresh = false): array {
        $cache = $this->cacheFactory->createDistributed('adminpage_storage');
        $cacheKey = 'organization_' . $organizationId;
        if ($refresh) {
            $cache->remove($cacheKey);
        }
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $users = $this->getUsers($organizationId);
        $projects = $this->getProjects($organizationId);
        $entitlement = $this->getPlanEntitlement($organizationId);
        $result = $this->summarize($users, $projects, $entitlement);
        $cache->set($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    private function getUsers(int $organizationId): array {
        $stmt = $this->db->prepare(
            'SELECT om.user_uid, om.role, u.displayname FROM *PREFIX*organization_members om LEFT JOIN *PREFIX*users u ON u.uid = om.user_uid WHERE om.organization_id = ? ORDER BY u.displayname ASC, om.user_uid ASC'
        );
        $stmt->execute([$organizationId]);
        $users = [];

        foreach ($stmt->fetchAll() as $row) {
            $userId = (string)$row['user_uid'];
            $used = null;
            $quota = null;
            try {
                $size = $this->rootFolder->getUserFolder($userId)->getSize(false);
                $used = $size >= 0 ? $size : null;
                $effectiveQuota = $this->userQuotaService->getEffectiveQuota($userId);
                $quota = $effectiveQuota !== null && $effectiveQuota > 0 ? $effectiveQuota : null;
            } catch (\Throwable $e) {
                $this->logger->warning('Unable to calculate organization member storage', [
                    'organizationId' => $organizationId,
                    'userId' => $userId,
                    'exception' => $e,
                ]);
            }

            $users[] = $this->resource(
                $userId,
                (string)($row['displayname'] ?: $userId),
                $used,
                $quota,
                ['role' => (string)$row['role']],
            );
        }

        return $users;
    }

    private function getProjects(int $organizationId): array {
        $stmt = $this->db->prepare(
            'SELECT cp.id, cp.name, cp.group_folder_id, fc.size FROM *PREFIX*custom_projects cp LEFT JOIN *PREFIX*filecache fc ON fc.fileid = cp.folder_id WHERE cp.organization_id = ? ORDER BY cp.name ASC'
        );
        $stmt->execute([$organizationId]);
        $rows = $stmt->fetchAll();
        $folderManager = null;

        if (class_exists(\OCA\GroupFolders\Folder\FolderManager::class)) {
            try {
                $folderManager = $this->serverContainer->get(\OCA\GroupFolders\Folder\FolderManager::class);
            } catch (\Throwable $e) {
                $this->logger->warning('Group Folders is unavailable for storage monitoring', ['exception' => $e]);
            }
        }

        $projects = [];
        foreach ($rows as $row) {
            $used = $row['size'] !== null && (int)$row['size'] >= 0 ? (int)$row['size'] : null;
            $quota = null;
            if ($folderManager !== null && $row['group_folder_id'] !== null) {
                try {
                    $folder = $folderManager->getFolder((int)$row['group_folder_id']);
                    $quota = $folder !== null && $folder->quota > 0 ? $folder->quota : null;
                } catch (\Throwable $e) {
                    $this->logger->warning('Unable to calculate project storage quota', [
                        'organizationId' => $organizationId,
                        'projectId' => (int)$row['id'],
                        'exception' => $e,
                    ]);
                }
            }

            $projects[] = $this->resource((string)$row['id'], (string)$row['name'], $used, $quota);
        }

        return $projects;
    }

    private function getPlanEntitlement(int $organizationId): ?array {
        $stmt = $this->db->prepare(
            'SELECT p.private_storage_per_user, p.shared_storage_per_project FROM *PREFIX*subscriptions s INNER JOIN *PREFIX*plans p ON p.id = s.plan_id WHERE s.organization_id = ? ORDER BY s.started_at DESC LIMIT 1'
        );
        $stmt->execute([$organizationId]);
        $row = $stmt->fetch();

        return $row ? [
            'privateStoragePerUserBytes' => max(0, (int)$row['private_storage_per_user']),
            'sharedStoragePerProjectBytes' => max(0, (int)$row['shared_storage_per_project']),
        ] : null;
    }

    private function resource(string $id, string $name, ?int $used, ?int $quota, array $extra = []): array {
        $percentage = $used !== null && $quota !== null ? round(($used / $quota) * 100, 1) : null;
        $status = 'unknown';
        if ($percentage !== null) {
            if ($percentage >= 100) {
                $status = 'critical';
            } elseif ($percentage >= 80) {
                $status = 'warning';
            } else {
                $status = 'healthy';
            }
        }

        return array_merge([
            'id' => $id,
            'name' => $name,
            'usedBytes' => $used,
            'quotaBytes' => $quota,
            'percentage' => $percentage,
            'status' => $status,
        ], $extra);
    }

    private function summarize(array $users, array $projects, ?array $entitlement): array {
        $private = $this->totals($users);
        $shared = $this->totals($projects);
        $used = $private['usedBytes'] + $shared['usedBytes'];
        $capacity = $private['capacityBytes'] + $shared['capacityBytes'];
        $resources = array_merge($users, $projects);

        return [
            'summary' => [
                'usedBytes' => $used,
                'capacityBytes' => $capacity,
                'percentage' => $capacity > 0 ? round(($used / $capacity) * 100, 1) : null,
                'private' => $private,
                'shared' => $shared,
                'complete' => $private['unknownCount'] === 0 && $shared['unknownCount'] === 0,
                'calculatedAt' => gmdate(DATE_ATOM),
            ],
            'thresholds' => [
                'warning' => 80,
                'critical' => 100,
                'warningCount' => count(array_filter($resources, static fn (array $resource): bool => $resource['status'] === 'warning')),
                'criticalCount' => count(array_filter($resources, static fn (array $resource): bool => $resource['status'] === 'critical')),
            ],
            'users' => $users,
            'projects' => $projects,
            'entitlement' => $entitlement,
        ];
    }

    private function totals(array $resources): array {
        $used = 0;
        $capacity = 0;
        $unknownCount = 0;
        foreach ($resources as $resource) {
            if ($resource['usedBytes'] === null || $resource['quotaBytes'] === null) {
                $unknownCount++;
            }
            $used += $resource['usedBytes'] ?? 0;
            $capacity += $resource['quotaBytes'] ?? 0;
        }

        return [
            'usedBytes' => $used,
            'capacityBytes' => $capacity,
            'resourceCount' => count($resources),
            'unknownCount' => $unknownCount,
        ];
    }
}
