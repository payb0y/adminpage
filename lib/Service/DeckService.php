<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;

class DeckService {

    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Fetch all task rows joined with boards and projects.
     * Returns raw rows with computed task_status and due_bucket.
     *
     * @return array
     */
    private function fetchTaskRows(): array {
        $sql = "
            SELECT
                c.id            AS task_id,
                c.title         AS task_title,
                b.id            AS board_id,
                b.title         AS board_title,
                s.title         AS stack_title,
                cp.id           AS project_id,
                cp.name         AS project_name,
                cp.status       AS project_status,
                c.duedate,
                c.done,
                c.archived,
                c.deleted_at,
                c.created_at    AS card_created_at,
                CASE
                    WHEN c.deleted_at <> 0     THEN 'deleted'
                    WHEN c.archived = 1        THEN 'archived'
                    WHEN c.done IS NOT NULL     THEN 'done'
                    ELSE 'open'
                END AS task_status,
                CASE
                    WHEN c.duedate IS NULL                                     THEN 'nodue'
                    WHEN DATE(c.duedate) < CURDATE()                           THEN 'overdue'
                    WHEN DATE(c.duedate) = CURDATE()                           THEN 'today'
                    WHEN DATE(c.duedate) = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'tomorrow'
                    WHEN DATE(c.duedate) <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'nextSevenDays'
                    ELSE 'later'
                END AS due_bucket
            FROM oc_deck_cards c
            JOIN oc_deck_stacks s  ON s.id  = c.stack_id
            JOIN oc_deck_boards b  ON b.id  = s.board_id
            LEFT JOIN (
                SELECT x.*
                FROM (
                    SELECT
                        p.*,
                        ROW_NUMBER() OVER (
                            PARTITION BY p.board_id
                            ORDER BY p.updated_at DESC, p.id DESC
                        ) AS rn
                    FROM oc_custom_projects p
                ) x
                WHERE x.rn = 1
            ) cp ON cp.board_id = CAST(b.id AS CHAR(64))
            ORDER BY b.id, c.id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Fetch label (discipline) data for all cards.
     *
     * @return array  card_id => [label_title, ...]
     */
    private function fetchCardLabels(): array {
        $sql = "
            SELECT
                al.card_id,
                l.title AS label_title
            FROM oc_deck_assigned_labels al
            JOIN oc_deck_labels l ON l.id = al.label_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['card_id']][] = $row['label_title'];
        }
        return $map;
    }

    /**
     * Build all Project Performance Analytics data from the Deck database.
     *
     * @return array  with keys: projectProgress, productivityByDiscipline,
     *                taskDelayProjects, taskCompletionProjects
     */
    public function getProjectPerformanceData(): array {
        $rows = $this->fetchTaskRows();
        $cardLabels = $this->fetchCardLabels();

        // ─── Group rows by project/board ───
        $projects = [];      // key = project_name|board_title => [...]
        $disciplines = [];   // label_title => { total, done }

        foreach ($rows as $row) {
            $projKey = $row['project_name'] ?? $row['board_title'];

            if (!isset($projects[$projKey])) {
                $projects[$projKey] = [
                    'name' => $projKey,
                    'board_id' => (int)$row['board_id'],
                    'tasks' => [],
                ];
            }
            $projects[$projKey]['tasks'][] = $row;

            // Discipline stats from labels
            $taskId = (int)$row['task_id'];
            $labels = $cardLabels[$taskId] ?? [];
            foreach ($labels as $label) {
                if (!isset($disciplines[$label])) {
                    $disciplines[$label] = ['total' => 0, 'done' => 0];
                }
                $disciplines[$label]['total']++;
                if ($row['task_status'] === 'done') {
                    $disciplines[$label]['done']++;
                }
            }
        }

        // ─── 1. Project Progress Comparison ───
        // Progress = (done tasks / total active tasks) × 100
        $projectProgress = [];
        foreach ($projects as $proj) {
            $total = 0;
            $done = 0;
            foreach ($proj['tasks'] as $t) {
                // Skip deleted tasks
                if ($t['task_status'] === 'deleted') {
                    continue;
                }
                $total++;
                if ($t['task_status'] === 'done') {
                    $done++;
                }
            }
            $progress = $total > 0 ? (int)round(($done / $total) * 100) : 0;
            $projectProgress[] = [
                'name' => $proj['name'],
                'progress' => $progress,
            ];
        }

        // ─── 2. Productivity by Discipline (label) ───
        $productivityByDiscipline = [];
        foreach ($disciplines as $label => $stats) {
            $progress = $stats['total'] > 0
                ? (int)round(($stats['done'] / $stats['total']) * 100)
                : 0;
            $productivityByDiscipline[] = [
                'name' => $label,
                'progress' => $progress,
            ];
        }
        // If no labels found, provide a default entry
        if (empty($productivityByDiscipline)) {
            $productivityByDiscipline[] = [
                'name' => 'All Tasks',
                'progress' => 0,
            ];
        }

        // ─── 3. Project Tasks Delay Overview (donut per project) ───
        $taskDelayProjects = [];
        foreach ($projects as $proj) {
            $onTime = 0;
            $delayed = 0;
            $blocked = 0;

            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'deleted') {
                    continue;
                }
                if ($t['task_status'] === 'done') {
                    // Check if it was completed past due
                    if ($t['duedate'] !== null && $t['done'] !== null) {
                        $doneDate = new \DateTime($t['done']);
                        $dueDate = new \DateTime($t['duedate']);
                        if ($doneDate > $dueDate) {
                            $delayed++;
                        } else {
                            $onTime++;
                        }
                    } else {
                        $onTime++;
                    }
                    continue;
                }
                // Open / archived tasks
                if ($t['task_status'] === 'archived') {
                    $blocked++;
                    continue;
                }
                // Open task – check due bucket
                if (in_array($t['due_bucket'], ['overdue'], true)) {
                    $delayed++;
                } elseif ($t['due_bucket'] === 'nodue') {
                    // No due date set – consider on-time
                    $onTime++;
                } else {
                    $onTime++;
                }
            }

            $total = $onTime + $delayed + $blocked;
            $taskDelayProjects[] = [
                'name' => $proj['name'],
                'chart' => [
                    'labels' => ['On-time Tasks', 'Delayed Tasks', 'Blocked Tasks'],
                    'data' => [
                        $total > 0 ? (int)round(($onTime / $total) * 100) : 0,
                        $total > 0 ? (int)round(($delayed / $total) * 100) : 0,
                        $total > 0 ? (int)round(($blocked / $total) * 100) : 0,
                    ],
                    'colors' => ['#2ec4b6', '#f4a261', '#e63946'],
                ],
            ];
        }

        // ─── 4. Task Completion Over Time (per project, weekly) ───
        $taskCompletionProjects = [];
        foreach ($projects as $proj) {
            // Gather done dates
            $completedDates = [];
            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'done' && $t['done'] !== null) {
                    $completedDates[] = new \DateTime($t['done']);
                }
            }

            if (empty($completedDates)) {
                // Still include the project with empty weeks
                $taskCompletionProjects[] = [
                    'name' => $proj['name'],
                    'weeks' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                    'data' => [0, 0, 0, 0, 0, 0],
                ];
                continue;
            }

            // Build 6-week buckets ending at current week
            $now = new \DateTime();
            $weekLabels = [];
            $weekCounts = [];

            for ($i = 5; $i >= 0; $i--) {
                $weekStart = (clone $now)->modify("-{$i} weeks")->modify('monday this week');
                $weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59);

                $label = $weekStart->format('M d');
                $weekLabels[] = $label;

                $count = 0;
                foreach ($completedDates as $d) {
                    if ($d >= $weekStart && $d <= $weekEnd) {
                        $count++;
                    }
                }
                $weekCounts[] = $count;
            }

            $taskCompletionProjects[] = [
                'name' => $proj['name'],
                'weeks' => $weekLabels,
                'data' => $weekCounts,
            ];
        }

        // Ensure we always have at least one entry for donut/area charts
        if (empty($taskDelayProjects)) {
            $taskDelayProjects[] = [
                'name' => 'No Projects',
                'chart' => [
                    'labels' => ['On-time Tasks', 'Delayed Tasks', 'Blocked Tasks'],
                    'data' => [0, 0, 0],
                    'colors' => ['#2ec4b6', '#f4a261', '#e63946'],
                ],
            ];
        }
        if (empty($taskCompletionProjects)) {
            $taskCompletionProjects[] = [
                'name' => 'No Projects',
                'weeks' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                'data' => [0, 0, 0, 0, 0, 0],
            ];
        }

        return [
            'projectProgress' => $projectProgress,
            'productivityByDiscipline' => $productivityByDiscipline,
            'taskDelayProjects' => $taskDelayProjects,
            'taskCompletionProjects' => $taskCompletionProjects,
        ];
    }
}
