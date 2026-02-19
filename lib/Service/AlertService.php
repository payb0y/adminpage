<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;

class AlertService {

    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Build all alerts from Nextcloud data.
     *
     * @return array  list of { badgeType, badgeLabel, description }
     */
    public function getAlerts(): array {
        $alerts = [];

        // ── 1. Overdue tasks (past due, not done, not deleted) ──
        $overdue = $this->countOverdueTasks();
        if ($overdue > 0) {
            $alerts[] = [
                'badgeType'   => 'action',
                'badgeLabel'  => 'Action required',
                'description' => $overdue . ' task' . ($overdue > 1 ? 's' : '') . ' overdue across projects',
            ];
        }

        // ── 2. Unassigned tasks ──
        $unassigned = $this->countUnassignedTasks();
        if ($unassigned > 0) {
            $alerts[] = [
                'badgeType'   => 'attention',
                'badgeLabel'  => 'Attention needed',
                'description' => $unassigned . ' task' . ($unassigned > 1 ? 's have' : ' has') . ' no assignee',
            ];
        }

        // ── 3. Tasks without due date ──
        $noDueDate = $this->countTasksWithoutDueDate();
        if ($noDueDate > 0) {
            $alerts[] = [
                'badgeType'   => 'attention',
                'badgeLabel'  => 'Attention needed',
                'description' => $noDueDate . ' task' . ($noDueDate > 1 ? 's have' : ' has') . ' no due date set',
            ];
        }

        // ── 4. Stalled projects (no card activity in 7+ days) ──
        $stalledProjects = $this->getStalledProjects();
        foreach ($stalledProjects as $proj) {
            $alerts[] = [
                'badgeType'   => 'attention',
                'badgeLabel'  => 'Attention needed',
                'description' => 'Project "' . $proj['name'] . '" has had no activity in 7+ days',
            ];
        }

        // ── 5. Projects with zero progress ──
        $zeroProgress = $this->getZeroProgressProjects();
        if (count($zeroProgress) > 0) {
            $names = implode(', ', $zeroProgress);
            $alerts[] = [
                'badgeType'   => 'attention',
                'badgeLabel'  => 'Attention needed',
                'description' => count($zeroProgress) . ' project' . (count($zeroProgress) > 1 ? 's have' : ' has') . ' zero completed tasks (' . $names . ')',
            ];
        }

        // ── 6. Pending app updates ──
        $pendingUpdates = $this->countPendingUpdates();
        if ($pendingUpdates > 0) {
            $alerts[] = [
                'badgeType'   => 'attention',
                'badgeLabel'  => 'Attention needed',
                'description' => $pendingUpdates . ' app update' . ($pendingUpdates > 1 ? 's' : '') . ' available',
            ];
        }

        // ── 7. Positive signals ──
        if ($overdue === 0) {
            $alerts[] = [
                'badgeType'   => 'ontrack',
                'badgeLabel'  => 'On track',
                'description' => 'No overdue tasks across all projects',
            ];
        }

        $totalActive = $this->countActiveProjectTasks();
        if ($totalActive > 0 && $unassigned === 0) {
            $alerts[] = [
                'badgeType'   => 'ontrack',
                'badgeLabel'  => 'On track',
                'description' => 'All active tasks have an assignee',
            ];
        }

        return $alerts;
    }

    /**
     * Count tasks that are past due date, not in Approved/Done, not deleted.
     */
    private function countOverdueTasks(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE c.duedate IS NOT NULL
              AND c.duedate < NOW()
              AND c.deleted_at = 0
              AND b.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count tasks with no user assigned (on project boards only).
     */
    private function countUnassignedTasks(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON b.id = CAST(cp.board_id AS UNSIGNED)
            LEFT JOIN *PREFIX*deck_assigned_users au ON au.card_id = c.id
            WHERE au.card_id IS NULL
              AND c.deleted_at = 0
              AND b.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count open tasks (not done/deleted) with no due date set.
     */
    private function countTasksWithoutDueDate(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE c.duedate IS NULL
              AND c.deleted_at = 0
              AND b.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Find projects where the most recent card activity is older than 7 days.
     */
    private function getStalledProjects(): array {
        $sevenDaysAgo = time() - (7 * 86400);

        $sql = "
            SELECT cp.name, MAX(c.last_modified) AS latest_activity
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            LEFT JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            GROUP BY cp.id, cp.name
            HAVING latest_activity IS NOT NULL AND latest_activity < ?
        ";
        $result = $this->db->prepare($sql);
        $result->bindValue(1, $sevenDaysAgo, \PDO::PARAM_INT);
        $result->execute();
        return $result->fetchAll();
    }

    /**
     * Find projects that have tasks but none in Approved/Done.
     */
    private function getZeroProgressProjects(): array {
        $sql = "
            SELECT cp.name,
                   COUNT(c.id) AS total_tasks,
                   SUM(CASE WHEN s.title = 'Approved/Done' THEN 1 ELSE 0 END) AS done_tasks
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            LEFT JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            GROUP BY cp.id, cp.name
            HAVING total_tasks > 0 AND done_tasks = 0
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return array_map(function ($row) {
            return $row['name'];
        }, $rows);
    }

    /**
     * Count pending app update notifications.
     */
    private function countPendingUpdates(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*notifications
            WHERE app = 'updatenotification'
              AND subject = 'update_available'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    /**
     * Count total active (non-deleted, non-done) tasks on project boards.
     */
    private function countActiveProjectTasks(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE c.deleted_at = 0
              AND b.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }
}
