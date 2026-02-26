<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;

class KpiService {

    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Build KPI card data scoped to a single organization.
     *
     * @param int $orgId
     * @return array  Three KPI cards: Operational, Subscription, Team
     */
    public function getKpis(int $orgId): array {
        return [
            $this->getOperationalKpi($orgId),
            $this->getResourcesKpi($orgId),
            $this->getSubscriptionKpi($orgId),
            $this->getTeamKpi($orgId),
        ];
    }

    // ─── OPERATIONAL ─────────────────────────────────────────────────────

    private function getOperationalKpi(int $orgId): array {
        $activeProjects = $this->countActiveProjects($orgId);
        $behindSchedule = $this->countProjectsBehindSchedule($orgId);
        $delayedTasks   = $this->countDelayedTasks($orgId);

        return [
            'id'        => 'operational',
            'title'     => 'Operational',
            'icon'      => 'icon-folder',
            'iconColor' => '#4A90D9',
            'metrics'   => [
                ['value' => (string)$activeProjects, 'label' => 'Active Projects'],
                ['value' => (string)$behindSchedule, 'label' => 'Behind Schedule'],
                ['value' => (string)$delayedTasks,   'label' => 'Delayed Tasks'],
            ],
        ];
    }

    private function countActiveProjects(int $orgId): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            WHERE cp.organization_id = ?
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countProjectsBehindSchedule(int $orgId): int {
        $sql = "
            SELECT COUNT(DISTINCT cp.id) AS cnt
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            JOIN *PREFIX*deck_cards c ON c.stack_id = s.id
            WHERE cp.organization_id = ?
              AND c.duedate IS NOT NULL
              AND c.duedate < NOW()
              AND c.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countDelayedTasks(int $orgId): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE cp.organization_id = ?
              AND c.duedate IS NOT NULL
              AND c.duedate < NOW()
              AND c.deleted_at = 0
              AND b.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    // ─── RESOURCES ───────────────────────────────────────────────────────

    private function getResourcesKpi(int $orgId): array {
        $whiteboards    = $this->countWhiteboards($orgId);
        $scrumbanBoards = $this->countScrumbanBoards($orgId);
        $files          = $this->countFiles($orgId);
        $notes          = $this->countNotes($orgId);

        return [
            'id'        => 'resources',
            'title'     => 'Resources',
            'icon'      => 'icon-files',
            'iconColor' => '#8B5CF6',
            'metrics'   => [
                ['value' => (string)$whiteboards,    'label' => 'Whiteboards'],
                ['value' => (string)$scrumbanBoards, 'label' => 'Scrumban Boards'],
                ['value' => (string)$files,          'label' => 'Files'],
                ['value' => (string)$notes,          'label' => 'Notes'],
            ],
        ];
    }

    private function countWhiteboards(int $orgId): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*custom_projects
            WHERE organization_id = ?
              AND white_board_id IS NOT NULL
              AND white_board_id != ''
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countScrumbanBoards(int $orgId): int {
        $sql = "
            SELECT COUNT(DISTINCT f.fileid) AS cnt
            FROM *PREFIX*filecache f
            INNER JOIN *PREFIX*custom_projects cp
                ON f.parent = cp.folder_id
               AND cp.organization_id = ?
            WHERE f.name = 'Scrumban'
              AND f.mimetype = (
                  SELECT id FROM *PREFIX*mimetypes WHERE mimetype = 'httpd/unix-directory'
              )
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countFiles(int $orgId): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*filecache f
            INNER JOIN *PREFIX*custom_projects cp
                ON f.parent = cp.folder_id
               AND cp.organization_id = ?
            WHERE f.mimetype NOT IN (
                SELECT id FROM *PREFIX*mimetypes WHERE mimetype IN ('httpd/unix-directory', 'application/vnd.excalidraw+json')
            )
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countNotes(int $orgId): int {
        /* Public notes: files inside 'Public Notes' folders under project dirs */
        $sqlPublic = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*filecache f
            INNER JOIN *PREFIX*filecache pnf
                ON f.parent = pnf.fileid
            INNER JOIN *PREFIX*custom_projects cp
                ON pnf.parent = cp.folder_id
               AND cp.organization_id = ?
            WHERE pnf.name = 'Public Notes'
              AND pnf.mimetype = (
                  SELECT id FROM *PREFIX*mimetypes WHERE mimetype = 'httpd/unix-directory'
              )
              AND f.mimetype != (
                  SELECT id FROM *PREFIX*mimetypes WHERE mimetype = 'httpd/unix-directory'
              )
        ";
        $result = $this->db->prepare($sqlPublic);
        $result->execute([$orgId]);
        $row = $result->fetch();
        $publicNotes = (int)($row['cnt'] ?? 0);

        /* Private card notes: entries linked to org's deck boards */
        $sqlPrivate = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*private_card_notes pcn
            INNER JOIN *PREFIX*deck_cards c  ON c.id = pcn.card_id
            INNER JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            INNER JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON CAST(cp.board_id AS UNSIGNED) = b.id
               AND cp.organization_id = ?
        ";
        $result = $this->db->prepare($sqlPrivate);
        $result->execute([$orgId]);
        $row = $result->fetch();
        $privateNotes = (int)($row['cnt'] ?? 0);

        return $publicNotes + $privateNotes;
    }

    // ─── SUBSCRIPTION ────────────────────────────────────────────────────

    private function getSubscriptionKpi(int $orgId): array {
        $sub = $this->fetchSubscription($orgId);

        return [
            'id'        => 'subscription',
            'title'     => 'Subscription',
            'icon'      => 'icon-quota',
            'iconColor' => '#2E9E5A',
            'metrics'   => [
                ['value' => $sub['planName'],    'label' => 'Current Plan'],
                ['value' => $sub['status'],      'label' => 'Status'],
            ],
        ];
    }

    private function fetchSubscription(int $orgId): array {
        $sql = "
            SELECT p.name AS plan_name, p.price, sub.status
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.organization_id = ?
            ORDER BY sub.id DESC
            LIMIT 1
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();

        if (!$row) {
            return ['planName' => 'None', 'price' => '—', 'status' => '—'];
        }

        $price = (float)$row['price'];
        return [
            'planName' => $row['plan_name'],
            'price'    => $price > 0 ? '€' . number_format($price, 2) : 'Free',
            'status'   => ucfirst($row['status']),
        ];
    }

    // ─── TEAM ────────────────────────────────────────────────────────────

    private function getTeamKpi(int $orgId): array {
        $memberCount = $this->countMembers($orgId);
        $memberLimit = $this->getMemberLimit($orgId);
        $adminCount  = $this->countAdmins($orgId);

        return [
            'id'        => 'team',
            'title'     => 'Team',
            'icon'      => 'icon-contacts-dark',
            'iconColor' => '#E67E5A',
            'metrics'   => [
                ['value' => (string)$memberCount, 'label' => 'Members'],
                ['value' => $memberLimit !== null ? (string)$memberLimit : '∞', 'label' => 'Member Limit'],
                ['value' => (string)$adminCount, 'label' => 'Admins'],
            ],
        ];
    }

    private function countMembers(int $orgId): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*organization_members WHERE organization_id = ?";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function getMemberLimit(int $orgId): ?int {
        $sql = "
            SELECT p.max_members
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.organization_id = ?
              AND sub.status = 'active'
            ORDER BY sub.id DESC
            LIMIT 1
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();

        if (!$row || $row['max_members'] === null || (int)$row['max_members'] === 0) {
            return null;
        }
        return (int)$row['max_members'];
    }

    private function countAdmins(int $orgId): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*organization_members WHERE organization_id = ? AND role = 'admin'";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
