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
            $this->getProjectsKpi($orgId),
            $this->getTasksKpi($orgId),
            $this->getResourcesKpi($orgId),
            $this->getSubscriptionKpi($orgId),
            $this->getTeamKpi($orgId),
        ];
    }

    // ─── PROJECTS ────────────────────────────────────────────────────────

    private function getProjectsKpi(int $orgId): array {
        $counts = $this->countProjectsByStatus($orgId);

        return [
            'id'        => 'projects',
            'title'     => 'Projects',
            'icon'      => 'icon-folder',
            'iconColor' => '#4A90D9',
            'metrics'   => [
                ['value' => (string)$counts['active'],   'label' => 'Active'],
                ['value' => (string)$counts['waiting'],  'label' => 'Waiting on Customer'],
                ['value' => (string)$counts['on_hold'],  'label' => 'On Hold'],
                ['value' => (string)$counts['done'],     'label' => 'Done'],
            ],
        ];
    }

    /**
     * Count projects grouped by status.
     * Status mapping: 0 = Active, 1 = Waiting on Customer, 2 = On Hold, 3 = Done
     */
    private function countProjectsByStatus(int $orgId): array {
        $sql = "
            SELECT cp.status, COUNT(*) AS cnt
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            WHERE cp.organization_id = ?
            GROUP BY cp.status
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);

        $map = ['active' => 0, 'waiting' => 0, 'on_hold' => 0, 'done' => 0];
        while ($row = $result->fetch()) {
            switch ((int)$row['status']) {
                case 0: $map['active']  = (int)$row['cnt']; break;
                case 1: $map['waiting'] = (int)$row['cnt']; break;
                case 2: $map['on_hold'] = (int)$row['cnt']; break;
                case 3: $map['done']    = (int)$row['cnt']; break;
            }
        }
        return $map;
    }

    // ─── TASKS ─────────────────────────────────────────────────────────

    private function getTasksKpi(int $orgId): array {
        $counts = $this->countTasksByStatus($orgId);

        return [
            'id'        => 'tasks',
            'title'     => 'Tasks',
            'icon'      => 'icon-checkmark',
            'iconColor' => '#E67E5A',
            'metrics'   => [
                ['value' => (string)$counts['overdue'],     'label' => 'Overdue'],
                ['value' => (string)$counts['today'],       'label' => 'Today'],
                ['value' => (string)$counts['upcoming'],    'label' => 'Upcoming'],
                ['value' => (string)$counts['in_progress'], 'label' => 'In Progress'],
            ],
        ];
    }

    /**
     * Count tasks (deck cards) grouped by temporal status.
     * Overdue  = duedate < NOW, not done, not in Done stack
     * Today    = duedate = today, not done, not in Done stack
     * Upcoming = duedate > NOW, not done, not in Done stack
     * In Progress = all open cards (not done, not in Done stack)
     */
    private function countTasksByStatus(int $orgId): array {
        $sql = "
            SELECT
                SUM(CASE WHEN c.duedate IS NOT NULL AND c.duedate < NOW() THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN c.duedate IS NOT NULL AND DATE(c.duedate) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN c.duedate IS NOT NULL AND c.duedate > NOW() THEN 1 ELSE 0 END) AS upcoming,
                COUNT(*) AS in_progress
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id AND b.deleted_at = 0
            JOIN *PREFIX*custom_projects cp
                ON CAST(cp.board_id AS UNSIGNED) = b.id
               AND cp.organization_id = ?
            WHERE c.deleted_at = 0
              AND c.done IS NULL
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();

        return [
            'overdue'     => (int)($row['overdue'] ?? 0),
            'today'       => (int)($row['today'] ?? 0),
            'upcoming'    => (int)($row['upcoming'] ?? 0),
            'in_progress' => (int)($row['in_progress'] ?? 0),
        ];
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
