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
     * Build detailed alerts data for the admin dashboard.
     *
     * @return array  structured alert data with summaries and per-project details
     */
    public function getAlerts(): array {
        $overdueTasks      = $this->getOverdueTaskDetails();
        $unassignedTasks   = $this->getUnassignedTaskDetails();
        $noDueDateTasks    = $this->getNoDueDateTaskDetails();
        $stalledProjects   = $this->getStalledProjects();
        $zeroProgressProjs = $this->getZeroProgressProjects();
        $pendingUpdates    = $this->getPendingUpdateDetails();

        // ── Summary counters ──
        $totalOverdue    = array_sum(array_column($overdueTasks, 'count'));
        $totalUnassigned = array_sum(array_column($unassignedTasks, 'count'));
        $totalNoDueDate  = array_sum(array_column($noDueDateTasks, 'count'));

        $summary = [
            [
                'label' => 'Overdue Tasks',
                'value' => $totalOverdue,
                'type'  => $totalOverdue > 0 ? 'danger' : 'success',
            ],
            [
                'label' => 'Unassigned Tasks',
                'value' => $totalUnassigned,
                'type'  => $totalUnassigned > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'No Due Date',
                'value' => $totalNoDueDate,
                'type'  => $totalNoDueDate > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'Stalled Projects',
                'value' => count($stalledProjects),
                'type'  => count($stalledProjects) > 0 ? 'warning' : 'success',
            ],
            [
                'label' => 'App Updates',
                'value' => count($pendingUpdates),
                'type'  => count($pendingUpdates) > 0 ? 'warning' : 'success',
            ],
        ];

        return [
            'summary'          => $summary,
            'overdueTasks'     => $overdueTasks,
            'unassignedTasks'  => $unassignedTasks,
            'noDueDateTasks'   => $noDueDateTasks,
            'stalledProjects'  => $stalledProjects,
            'zeroProgress'     => $zeroProgressProjs,
            'pendingUpdates'   => $pendingUpdates,
        ];
    }

    /**
     * Overdue tasks grouped by project, with task details.
     */
    private function getOverdueTaskDetails(): array {
        $sql = "
            SELECT
                cp.name         AS project_name,
                c.id            AS task_id,
                c.title         AS task_title,
                c.duedate,
                s.title         AS stack_title,
                DATEDIFF(NOW(), c.duedate) AS days_overdue
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
            ORDER BY cp.name, c.duedate ASC
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return $this->groupTasksByProject($rows);
    }

    /**
     * Unassigned tasks grouped by project, with task details.
     */
    private function getUnassignedTaskDetails(): array {
        $sql = "
            SELECT
                cp.name         AS project_name,
                c.id            AS task_id,
                c.title         AS task_title,
                c.duedate,
                s.title         AS stack_title
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
            ORDER BY cp.name, s.title, c.title
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return $this->groupTasksByProject($rows);
    }

    /**
     * Tasks without due date, grouped by project.
     */
    private function getNoDueDateTaskDetails(): array {
        $sql = "
            SELECT
                cp.name         AS project_name,
                c.id            AS task_id,
                c.title         AS task_title,
                s.title         AS stack_title
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp
                ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE c.duedate IS NULL
              AND c.deleted_at = 0
              AND b.deleted_at = 0
              AND s.title <> 'Approved/Done'
            ORDER BY cp.name, s.title, c.title
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return $this->groupTasksByProject($rows);
    }

    /**
     * Projects where the most recent card activity is older than 7 days.
     */
    private function getStalledProjects(): array {
        $sevenDaysAgo = time() - (7 * 86400);

        $sql = "
            SELECT
                cp.name         AS project_name,
                MAX(c.last_modified) AS latest_activity,
                COUNT(c.id)     AS total_tasks,
                SUM(CASE WHEN s.title = 'Approved/Done' THEN 1 ELSE 0 END) AS done_tasks
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
        $rows = $result->fetchAll();

        return array_map(function ($row) {
            $lastActive = (int)$row['latest_activity'];
            $daysAgo = (int)round((time() - $lastActive) / 86400);
            return [
                'project_name'    => $row['project_name'],
                'days_inactive'   => $daysAgo,
                'total_tasks'     => (int)$row['total_tasks'],
                'done_tasks'      => (int)$row['done_tasks'],
                'last_activity'   => date('Y-m-d H:i', $lastActive),
            ];
        }, $rows);
    }

    /**
     * Projects that have tasks but none in Approved/Done.
     */
    private function getZeroProgressProjects(): array {
        $sql = "
            SELECT
                cp.name         AS project_name,
                COUNT(c.id)     AS total_tasks
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            LEFT JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            GROUP BY cp.id, cp.name
            HAVING total_tasks > 0
               AND SUM(CASE WHEN s.title = 'Approved/Done' THEN 1 ELSE 0 END) = 0
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return array_map(function ($row) {
            return [
                'project_name' => $row['project_name'],
                'total_tasks'  => (int)$row['total_tasks'],
            ];
        }, $rows);
    }

    /**
     * Pending app update details.
     */
    private function getPendingUpdateDetails(): array {
        $sql = "
            SELECT object_type AS app_name, subject
            FROM *PREFIX*notifications
            WHERE app = 'updatenotification'
              AND subject = 'update_available'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        return $result->fetchAll();
    }

    /**
     * Group task rows by project_name.
     *
     * @return array [ { project_name, count, tasks: [...] }, ... ]
     */
    private function groupTasksByProject(array $rows): array {
        $grouped = [];
        foreach ($rows as $row) {
            $projName = $row['project_name'];
            if (!isset($grouped[$projName])) {
                $grouped[$projName] = [
                    'project_name' => $projName,
                    'count'        => 0,
                    'tasks'        => [],
                ];
            }
            $grouped[$projName]['count']++;
            $grouped[$projName]['tasks'][] = $row;
        }
        return array_values($grouped);
    }
}
