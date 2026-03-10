<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use DateTime;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;

class PublicTokenService {

    private IDBConnection $db;
    private ISecureRandom $secureRandom;

    public function __construct(IDBConnection $db, ISecureRandom $secureRandom) {
        $this->db = $db;
        $this->secureRandom = $secureRandom;
    }

    /**
     * Create a new public dashboard link for an organization.
     *
     * @return array The created link record (includes raw token for display)
     */
    public function createToken(int $orgId, string $createdBy, ?string $label = null, ?DateTime $expiresAt = null): array {
        $token = $this->secureRandom->generate(64, ISecureRandom::CHAR_ALPHANUMERIC);
        $now = new DateTime();

        $sql = "INSERT INTO *PREFIX*adminpage_public_links (token, org_id, label, enabled, expires_at, created_by, created_at)
                VALUES (?, ?, ?, 1, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $token,
            $orgId,
            $label,
            $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
            $createdBy,
            $now->format('Y-m-d H:i:s'),
        ]);

        $id = (int)$this->db->lastInsertId('*PREFIX*adminpage_public_links');

        return [
            'id' => $id,
            'token' => $token,
            'org_id' => $orgId,
            'label' => $label,
            'enabled' => true,
            'expires_at' => $expiresAt ? $expiresAt->format('Y-m-d H:i:s') : null,
            'created_by' => $createdBy,
            'created_at' => $now->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Validate a token and return the org ID if valid, null otherwise.
     */
    public function validateToken(string $token): ?int {
        $sql = "SELECT org_id, enabled, expires_at
                FROM *PREFIX*adminpage_public_links
                WHERE token = ?
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$token]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        if (!(bool)$row['enabled']) {
            return null;
        }

        if ($row['expires_at'] !== null) {
            $expires = new DateTime($row['expires_at']);
            if ($expires < new DateTime()) {
                return null;
            }
        }

        return (int)$row['org_id'];
    }

    /**
     * List all public links for an organization.
     */
    public function listTokens(int $orgId): array {
        $sql = "SELECT id, token, org_id, label, enabled, expires_at, created_by, created_at
                FROM *PREFIX*adminpage_public_links
                WHERE org_id = ?
                ORDER BY created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        $rows = $stmt->fetchAll();

        $now = new DateTime();
        return array_map(function ($row) use ($now) {
            $expired = false;
            if ($row['expires_at'] !== null) {
                $expired = new DateTime($row['expires_at']) < $now;
            }
            return [
                'id' => (int)$row['id'],
                'token' => $row['token'],
                'org_id' => (int)$row['org_id'],
                'label' => $row['label'],
                'enabled' => (bool)$row['enabled'],
                'expired' => $expired,
                'expires_at' => $row['expires_at'],
                'created_by' => $row['created_by'],
                'created_at' => $row['created_at'],
            ];
        }, $rows);
    }

    /**
     * Revoke (disable) a public link by ID.
     */
    public function revokeToken(int $id): void {
        $sql = "UPDATE *PREFIX*adminpage_public_links SET enabled = 0 WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Delete a public link by ID.
     */
    public function deleteToken(int $id): void {
        $sql = "DELETE FROM *PREFIX*adminpage_public_links WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }
}
