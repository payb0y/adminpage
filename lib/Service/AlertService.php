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
     * Build detailed alerts data scoped to a single organization.
     *
     * @param int $orgId
     * @return array  structured alert data with summaries and per-project details
     */
    public function getAlerts(int $orgId): array {
        $overdueTasks      = $this->getOverdueTaskDetails($orgId);
        $unassignedTasks   = $this->getUnassignedTaskDetails($orgId);
        $noDueDateTasks    = $this->getNoDueDateTaskDetails($orgId);
        $stalledProjects   = $this->getStalledProjects($orgId);
        $zeroProgressProjs = $this->getZeroProgressProjects($orgId);

        $totalOverdue    = array_sum(array_column($overdueTasks, 'count'));
        $totalUnassigned = array_sum(array_column($unassignedTasks, 'count'));
        $totalNoDueDate  = array_sum(array_column($noDueDateTasks, 'count'));

        $summary = [
            ['label' => 'Overdue Tasks',    'value' => $totalOverdue,    'type' => $totalOverdue > 0 ? 'danger' : 'success'],
            ['label' => 'Unassigned Tasks', 'value' => $totalUnassigned, 'type' => $totalUnassigned > 0 ? 'warning' : 'success'],
            ['label' => 'No Due Date',      'value' => $totalNoDueDate,  'type' => $totalNoDueDate > 0 ? 'warning' : 'success'],
            ['label' => 'Stalled Projects', 'value' => count($stalledProjects), 'type' => count($stalledProjects) > 0 ? 'warning' : 'success'],
        ];

        return [
            'summary'          => $summary,
            'overdueTasks'     => $overdueTasks,
            'unassignedTasks'  => $unassignedTasks,
            'noDueDateTasks'   => $noDueDateTasks,
            'stalledProjects'  => $stalledProjects,
            'zeroProgress'     => $zeroProgressProjs,
        ];
    }

    private function getOverdueTaskDetails(int $orgId): array {
        $sql = "
            SELECT cp.name AS project_name, c.id AS task_id, c.title AS task_title,
                   c.duedate, s.title AS stack_title, DATEDIFF(NOW(), c.duedate) AS days_overdue
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE cp.organization_id = ?
              AND c.duedate IS NOT NULL AND c.duedate < NOW()
              AND c.deleted_at = 0 AND b.deleted_at = 0 AND s.title <> 'Approved/Done'
            ORDER BY cp.name, c.duedate ASC
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        return $this->groupTasksByProject($result->fetchAll());
    }

    private function getUnassignedTaskDetails(int $orgId): array {
        $sql = "
            SELECT cp.name AS project_name, c.id AS task_id, c.title AS task_title,
                   c.duedate, s.title AS stack_title
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp ON b.id = CAST(cp.board_id AS UNSIGNED)
            LEFT JOIN *PREFIX*deck_assigned_users au ON au.card_id = c.id
            WHERE cp.organization_id = ?
              AND au.card_id IS NULL
              AND c.deleted_at = 0 AND b.deleted_at = 0 AND s.title <> 'Approved/Done'
            ORDER BY cp.name, s.title, c.title
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        return $this->groupTasksByProject($result->fetchAll());
    }

    private function getNoDueDateTaskDetails(int $orgId): array {
        $sql = "
            SELECT cp.name AS project_name, c.id AS task_id, c.title AS task_title, s.title AS stack_title
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            JOIN *PREFIX*deck_boards b ON b.id = s.board_id
            INNER JOIN *PREFIX*custom_projects cp ON b.id = CAST(cp.board_id AS UNSIGNED)
            WHERE cp.organization_id = ?
              AND c.duedate IS NULL
              AND c.deleted_at = 0 AND b.deleted_at = 0 AND s.title <> 'Approved/Done'
            ORDER BY cp.name, s.title, c.title
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        return $this->groupTasksByProject($result->fetchAll());
    }

    private function getStalledProjects(int $orgId): array {
        $sevenDaysAgo = time() - (7 * 86400);
        $sql = "
            SELECT cp.name AS project_name, MAX(c.last_modified) AS latest_activity,
                   COUNT(c.id) AS total_tasks,
                   SUM(CASE WHEN s.title = 'Approved/Done' THEN 1 ELSE 0 END) AS done_tasks
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b ON b.id = CAST(cp.board_id AS UNSIGNED) AND b.deleted_at = 0
            LEFT JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            WHERE cp.organization_id = ?
            GROUP BY cp.id, cp.name
            HAVING latest_activity IS NOT NULL AND latest_activity < ?
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId, $sevenDaysAgo]);
        return array_map(function ($row) {
            $lastActive = (int)$row['latest_activity'];
            return [
                'project_name'  => $row['project_name'],
                'days_inactive' => (int)round((time() - $lastActive) / 86400),
                'total_tasks'   => (int)$row['total_tasks'],
                'done_tasks'    => (int)$row['done_tasks'],
                'last_activity' => date('Y-m-d H:i', $lastActive),
            ];
        }, $result->fetchAll());
    }

    private function getZeroProgressProjects(int $orgId): array {
        $sql = "
            SELECT cp.name AS project_name, COUNT(c.id) AS total_tasks
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b ON b.id = CAST(cp.board_id AS UNSIGNED) AND b.deleted_at = 0
            LEFT JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            WHERE cp.organization_id = ?
            GROUP BY cp.id, cp.name
            HAVING total_tasks > 0 AND SUM(CASE WHEN s.title = 'Approved/Done' THEN 1 ELSE 0 END) = 0
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        return array_map(function ($row) {
            return ['project_name' => $row['project_name'], 'total_tasks' => (int)$row['total_tasks']];
        }, $result->fetchAll());
    }

    private function groupTasksByProject(array $rows): array {
        $grouped = [];
        foreach ($rows as $row) {
            $projName = $row['project_name'];
            if (!isset($grouped[$projName])) {
                $grouped[$projName] = ['project_name' => $projName, 'count' => 0, 'tasks' => []];
            }
            $grouped[$projName]['count']++;
            $grouped[$projName]['tasks'][] = $row;
        }
        return array_values($grouped);
    }
}
