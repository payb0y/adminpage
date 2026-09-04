<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Documents awaiting one user's signature, grouped by the project they belong to.
 *
 * Two halves, with very different confidence levels:
 *
 * 1. "Which requests await this user" is settled. The join below is the one
 *    employee_dashboard arrived at, kept deliberately identical — sr.signed IS
 *    NULL alone also matches drafts that were never sent and requests on
 *    deleted files, so f.status must be constrained too.
 *
 * 2. "Which project a request belongs to" comes from oc_project_signing_requests,
 *    which projectcreatoraio writes when it sends a document for signing. That
 *    link is authoritative when present and there is no second source: LibreSign
 *    moves the file out of the project folder into files/LibreSign/, so the
 *    stored path cannot be walked back to a project.
 *
 * A user who is not a signer sees nothing here, including an org admin who only
 * ever sends documents. That is intended: this answers "what is waiting on me".
 */
class SignatureService {

    /**
     * LibreSign document lifecycle: 0 draft, 1 able to sign, 2 partially
     * signed, 3 signed, 4 deleted. Only 1 and 2 are genuinely open.
     */
    private const OPEN_FILE_STATUSES = [1, 2];

    private IDBConnection $db;
    private LoggerInterface $logger;

    public function __construct(IDBConnection $db, LoggerInterface $logger) {
        $this->db = $db;
        $this->logger = $logger;
    }

    /**
     * @param int[]  $projectIds
     * @param string $uid the account that must sign
     * @return array<int, array<int, array<string, mixed>>> projectId => requests
     */
    public function getPendingByProject(array $projectIds, string $uid): array {
        if (empty($projectIds) || $uid === '') {
            return [];
        }

        $links = $this->fetchProjectLinks($projectIds);
        if (empty($links)) {
            return [];
        }

        // libresign_file_id is varchar(128) — wide enough for a uuid, though
        // projectcreatoraio currently stores the numeric libresign_file.id.
        // Accept either rather than casting in SQL, where the syntax differs
        // per driver and an empty string (written on a failed send) is fatal
        // to a numeric cast on postgres.
        $ids   = [];
        $uuids = [];
        foreach ($links as $link) {
            $ref = $link['ref'];
            if ($ref === '') {
                continue;
            }
            if (ctype_digit($ref)) {
                $ids[(int)$ref] = true;
            } else {
                $uuids[$ref] = true;
            }
        }
        if (empty($ids) && empty($uuids)) {
            return [];
        }

        $pending = $this->fetchPendingRequests(array_keys($ids), array_keys($uuids), $uid);
        if (empty($pending)) {
            return [];
        }

        $byProject = [];
        foreach ($links as $link) {
            $requests = $pending[$link['ref']] ?? [];
            foreach ($requests as $request) {
                $byProject[$link['projectId']][] = $request + [
                    'signatureFlow' => $link['flow'],
                ];
            }
        }

        foreach ($byProject as &$requests) {
            usort($requests, function ($a, $b) {
                return strcmp((string)$a['requestedAt'], (string)$b['requestedAt']);
            });
        }
        unset($requests);

        return $byProject;
    }

    /**
     * Project ↔ LibreSign document links for the given projects.
     *
     * @param int[] $projectIds
     * @return array<int, array{projectId: int, ref: string, flow: string}>
     */
    private function fetchProjectLinks(array $projectIds): array {
        $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
        $sql = "
            SELECT project_id, libresign_file_id, signature_flow
            FROM *PREFIX*project_signing_requests
            WHERE project_id IN ({$placeholders})
              AND libresign_file_id IS NOT NULL
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $idx = 1;
            foreach ($projectIds as $projectId) {
                $stmt->bindValue($idx++, $projectId, \PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            // The table ships with projectcreatoraio; an install without it
            // should show no signatures, not a 500.
            $this->logger->debug('project signing links unavailable', [
                'app'       => 'adminpage',
                'exception' => $e,
            ]);
            return [];
        }

        $links = [];
        foreach ($rows as $row) {
            $links[] = [
                'projectId' => (int)$row['project_id'],
                'ref'       => trim((string)$row['libresign_file_id']),
                'flow'      => (string)($row['signature_flow'] ?? ''),
            ];
        }
        return $links;
    }

    /**
     * Open sign requests for the given documents that $uid personally must sign,
     * keyed by BOTH the document id and its uuid so either link encoding resolves.
     *
     * @param int[]    $fileIds
     * @param string[] $fileUuids
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function fetchPendingRequests(array $fileIds, array $fileUuids, string $uid): array {
        $params = [];
        $refClauses = [];

        if (!empty($fileIds)) {
            $refClauses[] = 'f.id IN (' . implode(',', array_fill(0, count($fileIds), '?')) . ')';
            foreach ($fileIds as $fileId) {
                $params[] = [$fileId, \PDO::PARAM_INT];
            }
        }
        if (!empty($fileUuids)) {
            $refClauses[] = 'f.uuid IN (' . implode(',', array_fill(0, count($fileUuids), '?')) . ')';
            foreach ($fileUuids as $fileUuid) {
                $params[] = [$fileUuid, \PDO::PARAM_STR];
            }
        }

        $statusPlaceholders = implode(',', array_fill(0, count(self::OPEN_FILE_STATUSES), '?'));
        foreach (self::OPEN_FILE_STATUSES as $status) {
            $params[] = [$status, \PDO::PARAM_INT];
        }
        $params[] = [$uid, \PDO::PARAM_STR];

        // sr.uuid, NOT f.uuid: /apps/signatures/p/sign/{uuid} resolves the
        // per-signer sign_request, and the file uuid yields "Invalid UUID".
        // sr.display_name is the SIGNER; the sender is f.user_id.
        $sql = "
            SELECT f.id AS file_id, f.uuid AS file_uuid, f.name AS file_name,
                   f.user_id AS requested_by,
                   sr.uuid AS sign_uuid, sr.display_name, sr.created_at
            FROM *PREFIX*libresign_sign_request sr
            INNER JOIN *PREFIX*libresign_file f
                ON f.id = sr.file_id
            INNER JOIN *PREFIX*libresign_identify_method im
                ON im.sign_request_id = sr.id
            WHERE (" . implode(' OR ', $refClauses) . ")
              AND sr.signed IS NULL
              AND f.status IN ({$statusPlaceholders})
              AND im.identifier_key = 'account'
              AND im.identifier_value = ?
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $idx = 1;
            foreach ($params as $param) {
                $stmt->bindValue($idx++, $param[0], $param[1]);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $this->logger->debug('LibreSign requests unavailable', [
                'app'       => 'adminpage',
                'exception' => $e,
            ]);
            return [];
        }

        $byRef = [];
        foreach ($rows as $row) {
            $request = [
                'signUuid'    => (string)$row['sign_uuid'],
                'fileName'    => (string)$row['file_name'],
                'signerName'  => (string)$row['display_name'],
                'requestedBy' => (string)$row['requested_by'],
                'requestedAt' => $row['created_at'],
            ];
            $byRef[(string)$row['file_id']][]  = $request;
            $byRef[(string)$row['file_uuid']][] = $request;
        }
        return $byRef;
    }
}
