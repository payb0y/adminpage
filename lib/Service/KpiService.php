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
            $this->getTimelineKpi($orgId),
        ];
    }

    // ─── PROJECTS ────────────────────────────────────────────────────────

    private function getProjectsKpi(int $orgId): array {
        $counts = $this->countProjectsByStatus($orgId);
        $withIssue = $this->countProjectsWithIssue($orgId);

        return [
            'id'        => 'projects',
            'title'     => 'Projects',
            'icon'      => 'icon-folder',
            'iconColor' => '#4A90D9',
            'metrics'   => [
                ['value' => (string)$counts['active'],   'label' => 'Active'],
                ['value' => (string)$counts['waiting'],  'label' => 'W.o.c.'],
                ['value' => (string)$counts['on_hold'],  'label' => 'On Hold'],
                ['value' => (string)$counts['done'],     'label' => 'Done'],
                ['value' => (string)$withIssue,          'label' => 'With Issue'],
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

    /**
     * Count projects that have at least one overdue task.
     */
    private function countProjectsWithIssue(int $orgId): int {
        $sql = "
            SELECT COUNT(DISTINCT cp.id) AS cnt
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
               AND b.deleted_at = 0
            INNER JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            INNER JOIN *PREFIX*deck_cards c
                ON c.stack_id = s.id
               AND c.deleted_at = 0
               AND c.done IS NULL
            WHERE cp.organization_id = ?
              AND s.title <> 'Approved/Done'
              AND c.duedate IS NOT NULL
              AND DATE(c.duedate) < CURDATE()
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    // ─── TASKS ─────────────────────────────────────────────────────────

    private function getTasksKpi(int $orgId): array {
        $counts = $this->countTasksByStatus($orgId);
        $oldest = $this->findOldestTask($orgId);

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
                ['value' => (string)$counts['non_due'],     'label' => 'Non Due'],
                ['value' => $counts['avg_days'] . 'd',      'label' => 'Avg Days Active'],
            ],
            'oldestTask' => $oldest,
        ];
    }

    /**
     * Find the oldest open (non-done, non-deleted) task across all projects.
     */
    private function findOldestTask(int $orgId): ?array {
        $sql = "
            SELECT c.title AS task_title,
                   c.created_at AS task_created_at,
                   cp.id AS project_id,
                   cp.name AS project_name
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id AND b.deleted_at = 0
            JOIN *PREFIX*custom_projects cp
                ON CAST(cp.board_id AS UNSIGNED) = b.id
               AND cp.organization_id = ?
            WHERE c.deleted_at = 0
              AND c.done IS NULL
              AND s.title <> 'Approved/Done'
            ORDER BY c.created_at ASC
            LIMIT 1
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();
        if (!$row) return null;

        $createdTs = (int)$row['task_created_at'];
        $ageDays = (int)round((time() - $createdTs) / 86400);

        return [
            'taskTitle'   => $row['task_title'],
            'projectId'   => (int)$row['project_id'],
            'projectName' => $row['project_name'],
            'createdAt'   => date('Y-m-d H:i:s', $createdTs),
            'ageDays'     => $ageDays,
        ];
    }

    /**
     * Count tasks (deck cards) grouped by temporal status.
     * Overdue  = DATE(duedate) < CURDATE, not done, not in Done stack
     * Today    = DATE(duedate) = CURDATE, not done, not in Done stack
     * Upcoming = DATE(duedate) > CURDATE, not done, not in Done stack
     * In Progress = all open cards (not done, not in Done stack)
     */
    private function countTasksByStatus(int $orgId): array {
        $sql = "
            SELECT
                SUM(CASE WHEN c.duedate IS NOT NULL AND DATE(c.duedate) < CURDATE() THEN 1 ELSE 0 END) AS overdue,
                SUM(CASE WHEN c.duedate IS NOT NULL AND DATE(c.duedate) = CURDATE() THEN 1 ELSE 0 END) AS today,
                SUM(CASE WHEN c.duedate IS NOT NULL AND DATE(c.duedate) > CURDATE() THEN 1 ELSE 0 END) AS upcoming,
                COUNT(*) AS in_progress,
                SUM(CASE WHEN c.duedate IS NULL THEN 1 ELSE 0 END) AS non_due,
                COALESCE(ROUND(AVG(DATEDIFF(NOW(), FROM_UNIXTIME(c.created_at)))), 0) AS avg_days
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
            'non_due'     => (int)($row['non_due'] ?? 0),
            'avg_days'    => (int)($row['avg_days'] ?? 0),
        ];
    }

    // ─── RESOURCES ───────────────────────────────────────────────────────

    private function getResourcesKpi(int $orgId): array {
        $whiteboards    = $this->countWhiteboards($orgId);
        $scrumbanBoards = $this->countScrumbanBoards($orgId);
        $files          = $this->countFilesSplit($orgId);
        $notes          = $this->countNotesSplit($orgId);

        return [
            'id'        => 'resources',
            'title'     => 'Resources',
            'icon'      => 'icon-files',
            'iconColor' => '#8B5CF6',
            'metrics'   => [
                ['value' => (string)$whiteboards,                                         'label' => 'Whiteboards'],
                ['value' => (string)$scrumbanBoards,                                      'label' => 'Scrumban Boards'],
                ['value' => $files['public'] . ' pub / ' . $files['private'] . ' priv',   'label' => 'Files'],
                ['value' => $notes['public'] . ' pub / ' . $notes['private'] . ' priv',   'label' => 'Notes'],
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

    /**
     * Count files split into public (in project folder) and private (shared to specific users).
     */
    private function countFilesSplit(int $orgId): array {
        /* Public files: non-directory, non-whiteboard files directly in project folder */
        $sqlPublic = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*filecache f
            INNER JOIN *PREFIX*custom_projects cp
                ON f.parent = cp.folder_id
               AND cp.organization_id = ?
            WHERE f.mimetype NOT IN (
                SELECT id FROM *PREFIX*mimetypes WHERE mimetype IN ('httpd/unix-directory', 'application/vnd.excalidraw+json')
            )
        ";
        $result = $this->db->prepare($sqlPublic);
        $result->execute([$orgId]);
        $row = $result->fetch();
        $publicFiles = (int)($row['cnt'] ?? 0);

        /* Private files: files shared from project folders to individual users */
        $sqlPrivate = "
            SELECT COUNT(DISTINCT sh.id) AS cnt
            FROM *PREFIX*share sh
            INNER JOIN *PREFIX*filecache f ON f.fileid = sh.file_source
            INNER JOIN *PREFIX*custom_projects cp
                ON f.parent = cp.folder_id
               AND cp.organization_id = ?
            WHERE sh.share_type = 0
        ";
        $result = $this->db->prepare($sqlPrivate);
        $result->execute([$orgId]);
        $row = $result->fetch();
        $privateFiles = (int)($row['cnt'] ?? 0);

        return ['public' => $publicFiles, 'private' => $privateFiles];
    }

    /**
     * Count notes split into public ('Public Notes' folder) and private (card notes).
     */
    private function countNotesSplit(int $orgId): array {
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

        return ['public' => $publicNotes, 'private' => $privateNotes];
    }

    // ─── TIMELINE ──────────────────────────────────────────────────────

    private function getTimelineKpi(int $orgId): array {
        $completionRate     = $this->avgCompletionRate($orgId);
        $coordinationWeeks  = $this->avgCoordinationPending($orgId);
        $prepWeeks          = $this->avgRequiredPrepTime($orgId);

        return [
            'id'        => 'timeline',
            'title'     => 'Timeline',
            'icon'      => 'icon-calendar',
            'iconColor' => '#0EA5E9',
            'metrics'   => [
                ['value' => $completionRate . '%', 'label' => 'Avg Completion Rate'],
                ['value' => $coordinationWeeks,    'label' => 'Avg Coordination Pending'],
                ['value' => $prepWeeks,            'label' => 'Avg Required Prep Time'],
            ],
        ];
    }

    /**
     * Average completion rate across all projects.
     * Completion = done tasks / total tasks * 100 per project, then averaged.
     */
    private function avgCompletionRate(int $orgId): string {
        $sql = "
            SELECT
                cp.id AS project_id,
                COUNT(*) AS total,
                SUM(CASE
                    WHEN c.done IS NOT NULL OR s.title = 'Approved/Done'
                    THEN 1 ELSE 0
                END) AS done_cnt
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id AND b.deleted_at = 0
            JOIN *PREFIX*custom_projects cp
                ON CAST(cp.board_id AS UNSIGNED) = b.id
               AND cp.organization_id = ?
            WHERE c.deleted_at = 0
            GROUP BY cp.id
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);

        $rates = [];
        while ($row = $result->fetch()) {
            $total = (int)$row['total'];
            if ($total > 0) {
                $rates[] = ((int)$row['done_cnt'] / $total) * 100;
            }
        }

        if (empty($rates)) {
            return '0';
        }
        return (string)round(array_sum($rates) / count($rates));
    }

    /**
     * Average coordination pending time in weeks.
     * Measures weeks from each project's request_date start_date until NOW.
     */
    private function avgCoordinationPending(int $orgId): string {
        $sql = "
            SELECT
                ROUND(AVG(DATEDIFF(NOW(), pti.start_date) / 7)) AS avg_weeks
            FROM *PREFIX*project_timeline_items pti
            INNER JOIN *PREFIX*custom_projects cp
                ON cp.id = pti.project_id
               AND cp.organization_id = ?
            WHERE pti.system_key = 'request_date'
              AND pti.start_date IS NOT NULL
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();

        $weeks = (int)($row['avg_weeks'] ?? 0);
        return $weeks . ' wk' . ($weeks !== 1 ? 's' : '');
    }

    /**
     * Average required preparation time in weeks.
     * Measures weeks between start_date and end_date of required_preparation items.
     */
    private function avgRequiredPrepTime(int $orgId): string {
        $sql = "
            SELECT
                ROUND(AVG(DATEDIFF(pti.end_date, pti.start_date) / 7)) AS avg_weeks
            FROM *PREFIX*project_timeline_items pti
            INNER JOIN *PREFIX*custom_projects cp
                ON cp.id = pti.project_id
               AND cp.organization_id = ?
            WHERE pti.system_key = 'required_preparation'
              AND pti.start_date IS NOT NULL
              AND pti.end_date IS NOT NULL
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        $row = $result->fetch();

        $weeks = (int)($row['avg_weeks'] ?? 0);
        return $weeks . ' wk' . ($weeks !== 1 ? 's' : '');
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
