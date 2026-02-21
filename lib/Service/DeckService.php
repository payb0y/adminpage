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
     * Fetch all projects that have an existing (non-deleted) board.
     *
     * @return array
     */
    private function fetchProjects(): array {
        $sql = "
            SELECT
                cp.id           AS project_id,
                cp.name         AS project_name,
                cp.status       AS project_status,
                cp.board_id     AS project_board_id,
                b.id            AS board_id,
                b.title         AS board_title
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            ORDER BY cp.id
        ";

        $result = $this->db->prepare($sql);
        $result->execute();
        return $result->fetchAll();
    }

    /**
     * Fetch all task rows for given board IDs, with computed status/due info.
     *
     * @param int[] $boardIds
     * @return array
     */
    private function fetchTaskRowsForBoards(array $boardIds): array {
        if (empty($boardIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));

        $sql = "
            SELECT
                c.id            AS task_id,
                c.title         AS task_title,
                b.id            AS board_id,
                b.title         AS board_title,
                s.title         AS stack_title,
                c.duedate,
                c.archived,
                c.deleted_at,
                c.last_modified,
                c.created_at    AS card_created_at,
                CASE
                    WHEN c.deleted_at <> 0          THEN 'deleted'
                    WHEN c.archived = 1             THEN 'archived'
                    WHEN s.title = 'Approved/Done'  THEN 'done'
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
            FROM *PREFIX*deck_cards c
            JOIN *PREFIX*deck_stacks s  ON s.id  = c.stack_id
            JOIN *PREFIX*deck_boards b  ON b.id  = s.board_id
            WHERE b.id IN ({$placeholders})
            ORDER BY b.id, c.id
        ";

        $result = $this->db->prepare($sql);
        $idx = 1;
        foreach ($boardIds as $bid) {
            $result->bindValue($idx++, $bid, \PDO::PARAM_INT);
        }
        $result->execute();
        return $result->fetchAll();
    }

    /**
     * Fetch label (discipline) data for cards belonging to given board IDs.
     *
     * @param int[] $boardIds
     * @return array  card_id => [label_title, ...]
     */
    private function fetchCardLabels(array $boardIds): array {
        if (empty($boardIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));

        $sql = "
            SELECT
                al.card_id,
                l.title AS label_title
            FROM *PREFIX*deck_assigned_labels al
            JOIN *PREFIX*deck_labels l ON l.id = al.label_id
            JOIN *PREFIX*deck_cards c ON c.id = al.card_id
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            WHERE s.board_id IN ({$placeholders})
        ";

        $result = $this->db->prepare($sql);
        $idx = 1;
        foreach ($boardIds as $bid) {
            $result->bindValue($idx++, $bid, \PDO::PARAM_INT);
        }
        $result->execute();
        $rows = $result->fetchAll();

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['card_id']][] = $row['label_title'];
        }
        return $map;
    }

    /**
     * Build all Project Performance Analytics data from the Deck database.
     *
     * Approach: start from projects → get linked boards → fetch tasks & labels
     * only for those boards. Projects with 0 cards still appear in every widget.
     *
     * @return array  with keys: projectProgress, productivityByDiscipline,
     *                taskDelayProjects, taskCompletionProjects
     */
    public function getProjectPerformanceData(): array {
        // 1. Fetch all projects that have a valid (non-deleted) board
        $projectRows = $this->fetchProjects();

        if (empty($projectRows)) {
            return $this->emptyResponse();
        }

        // Build a project map keyed by board_id, and collect board IDs
        $projectMap = [];   // board_id => { name, board_id, tasks[] }
        $boardIds   = [];

        foreach ($projectRows as $p) {
            $bid = (int)$p['board_id'];
            $boardIds[] = $bid;
            $projectMap[$bid] = [
                'name'     => $p['project_name'],
                'board_id' => $bid,
                'tasks'    => [],
            ];
        }

        // 2. Fetch tasks and labels scoped to those boards
        $taskRows   = $this->fetchTaskRowsForBoards($boardIds);
        $cardLabels = $this->fetchCardLabels($boardIds);

        // 3. Group tasks into their project by board_id
        foreach ($taskRows as $row) {
            $bid = (int)$row['board_id'];
            if (isset($projectMap[$bid])) {
                $projectMap[$bid]['tasks'][] = $row;
            }
        }

        // 4. Build discipline (label) stats across all tasks
        $disciplines = [];   // label_title => { total, done }
        foreach ($taskRows as $row) {
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

        // ─── Widget 1: Project Progress Comparison ───
        $projectProgress = [];
        foreach ($projectMap as $proj) {
            $total = 0;
            $done  = 0;
            foreach ($proj['tasks'] as $t) {
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
                'name'     => $proj['name'],
                'progress' => $progress,
            ];
        }

        // ─── Widget 2: Productivity by Discipline (label) ───
        $productivityByDiscipline = [];
        foreach ($disciplines as $label => $stats) {
            $progress = $stats['total'] > 0
                ? (int)round(($stats['done'] / $stats['total']) * 100)
                : 0;
            $productivityByDiscipline[] = [
                'name'     => $label,
                'progress' => $progress,
            ];
        }
        if (empty($productivityByDiscipline)) {
            $productivityByDiscipline[] = [
                'name'     => 'All Tasks',
                'progress' => 0,
            ];
        }

        // ─── Widget 3: Project Tasks Delay Overview (donut per project) ───
        $taskDelayProjects = [];
        foreach ($projectMap as $proj) {
            $onTime  = 0;
            $delayed = 0;
            $blocked = 0;

            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'deleted') {
                    continue;
                }
                if ($t['task_status'] === 'done') {
                    if ($t['duedate'] !== null && $t['last_modified'] !== null) {
                        $doneDate = (new \DateTime())->setTimestamp((int)$t['last_modified']);
                        $dueDate  = new \DateTime($t['duedate']);
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
                if ($t['task_status'] === 'archived') {
                    $blocked++;
                    continue;
                }
                // Open task
                if ($t['due_bucket'] === 'overdue') {
                    $delayed++;
                } else {
                    $onTime++;
                }
            }

            $total = $onTime + $delayed + $blocked;
            $taskDelayProjects[] = [
                'name'  => $proj['name'],
                'chart' => [
                    'labels' => ['On-time Tasks', 'Delayed Tasks', 'Blocked Tasks'],
                    'data'   => [
                        $total > 0 ? (int)round(($onTime  / $total) * 100) : 0,
                        $total > 0 ? (int)round(($delayed / $total) * 100) : 0,
                        $total > 0 ? (int)round(($blocked / $total) * 100) : 0,
                    ],
                    'colors' => ['#2ec4b6', '#f4a261', '#e63946'],
                ],
            ];
        }

        // ─── Widget 4: Task Completion Over Time (per project, weekly) ───
        $taskCompletionProjects = [];
        foreach ($projectMap as $proj) {
            $completedDates = [];
            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'done' && $t['last_modified'] !== null) {
                    $completedDates[] = (new \DateTime())->setTimestamp((int)$t['last_modified']);
                }
            }

            if (empty($completedDates)) {
                $taskCompletionProjects[] = [
                    'name'  => $proj['name'],
                    'weeks' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                    'data'  => [0, 0, 0, 0, 0, 0],
                ];
                continue;
            }

            $now        = new \DateTime();
            $weekLabels = [];
            $weekCounts = [];

            for ($i = 5; $i >= 0; $i--) {
                $weekStart = (clone $now)->modify("-{$i} weeks")->modify('monday this week');
                $weekEnd   = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59);

                $weekLabels[] = $weekStart->format('M d');

                $count = 0;
                foreach ($completedDates as $d) {
                    if ($d >= $weekStart && $d <= $weekEnd) {
                        $count++;
                    }
                }
                $weekCounts[] = $count;
            }

            $taskCompletionProjects[] = [
                'name'  => $proj['name'],
                'weeks' => $weekLabels,
                'data'  => $weekCounts,
            ];
        }

        return [
            'projectProgress'         => $projectProgress,
            'productivityByDiscipline' => $productivityByDiscipline,
            'taskDelayProjects'        => $taskDelayProjects,
            'taskCompletionProjects'   => $taskCompletionProjects,
        ];
    }

    /**
     * Return a safe empty response when no projects exist.
     */
    private function emptyResponse(): array {
        return [
            'projectProgress' => [],
            'productivityByDiscipline' => [
                ['name' => 'All Tasks', 'progress' => 0],
            ],
            'taskDelayProjects' => [
                [
                    'name'  => 'No Projects',
                    'chart' => [
                        'labels' => ['On-time Tasks', 'Delayed Tasks', 'Blocked Tasks'],
                        'data'   => [0, 0, 0],
                        'colors' => ['#2ec4b6', '#f4a261', '#e63946'],
                    ],
                ],
            ],
            'taskCompletionProjects' => [
                [
                    'name'  => 'No Projects',
                    'weeks' => ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
                    'data'  => [0, 0, 0, 0, 0, 0],
                ],
            ],
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Detail data for drill-down modals
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Get the task-level detail behind each performance widget, used for
     * drill-down modals in the frontend.
     *
     * @return array  with keys matching the 4 widgets
     */
    public function getPerformanceDetails(): array {
        $projectRows = $this->fetchProjects();
        if (empty($projectRows)) {
            return [
                'progressDetails'       => [],
                'disciplineDetails'     => [],
                'delayDetails'          => [],
                'completionDetails'     => [],
            ];
        }

        $boardIds = [];
        $projectMap = [];
        foreach ($projectRows as $p) {
            $bid = (int)$p['board_id'];
            $boardIds[] = $bid;
            $projectMap[$bid] = [
                'name'     => $p['project_name'],
                'board_id' => $bid,
                'tasks'    => [],
            ];
        }

        $taskRows   = $this->fetchTaskRowsForBoards($boardIds);
        $cardLabels = $this->fetchCardLabels($boardIds);

        foreach ($taskRows as $row) {
            $bid = (int)$row['board_id'];
            if (isset($projectMap[$bid])) {
                $projectMap[$bid]['tasks'][] = $row;
            }
        }

        return [
            'progressDetails'   => $this->buildProgressDetails($projectMap),
            'disciplineDetails' => $this->buildDisciplineDetails($taskRows, $cardLabels),
            'delayDetails'      => $this->buildDelayDetails($projectMap),
            'completionDetails' => $this->buildCompletionDetails($projectMap),
        ];
    }

    /**
     * Progress detail: per-project list of tasks with status and due info.
     */
    private function buildProgressDetails(array $projectMap): array {
        $result = [];
        foreach ($projectMap as $proj) {
            $total = 0;
            $done  = 0;
            $tasks = [];
            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'deleted') {
                    continue;
                }
                $total++;
                if ($t['task_status'] === 'done') {
                    $done++;
                }
                $tasks[] = [
                    'title'  => $t['task_title'],
                    'status' => $t['task_status'],
                    'stack'  => $t['stack_title'],
                    'due'    => $t['duedate'],
                ];
            }
            $result[] = [
                'name'     => $proj['name'],
                'total'    => $total,
                'done'     => $done,
                'progress' => $total > 0 ? (int)round(($done / $total) * 100) : 0,
                'tasks'    => $tasks,
            ];
        }
        return $result;
    }

    /**
     * Discipline detail: per-label list of tasks.
     */
    private function buildDisciplineDetails(array $taskRows, array $cardLabels): array {
        $disciplines = []; // label => { total, done, tasks[] }
        foreach ($taskRows as $row) {
            $taskId = (int)$row['task_id'];
            $labels = $cardLabels[$taskId] ?? [];
            if (empty($labels)) {
                $labels = ['Unlabelled'];
            }
            foreach ($labels as $label) {
                if (!isset($disciplines[$label])) {
                    $disciplines[$label] = ['total' => 0, 'done' => 0, 'tasks' => []];
                }
                $disciplines[$label]['total']++;
                if ($row['task_status'] === 'done') {
                    $disciplines[$label]['done']++;
                }
                $disciplines[$label]['tasks'][] = [
                    'title'   => $row['task_title'],
                    'status'  => $row['task_status'],
                    'project' => $row['board_title'],
                    'stack'   => $row['stack_title'],
                ];
            }
        }

        $result = [];
        foreach ($disciplines as $label => $data) {
            $result[] = [
                'name'     => $label,
                'total'    => $data['total'],
                'done'     => $data['done'],
                'progress' => $data['total'] > 0 ? (int)round(($data['done'] / $data['total']) * 100) : 0,
                'tasks'    => $data['tasks'],
            ];
        }
        return $result;
    }

    /**
     * Delay detail: per-project task-level on-time / delayed / blocked breakdown.
     */
    private function buildDelayDetails(array $projectMap): array {
        $result = [];
        foreach ($projectMap as $proj) {
            $tasks = [];
            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'deleted') {
                    continue;
                }

                $category = 'on-time';
                $daysOverdue = null;

                if ($t['task_status'] === 'done') {
                    if ($t['duedate'] !== null && $t['last_modified'] !== null) {
                        $doneDate = (new \DateTime())->setTimestamp((int)$t['last_modified']);
                        $dueDate  = new \DateTime($t['duedate']);
                        if ($doneDate > $dueDate) {
                            $category = 'delayed';
                            $daysOverdue = (int)$doneDate->diff($dueDate)->days;
                        }
                    }
                } elseif ($t['task_status'] === 'archived') {
                    $category = 'blocked';
                } elseif ($t['due_bucket'] === 'overdue') {
                    $category = 'delayed';
                    if ($t['duedate'] !== null) {
                        $now = new \DateTime();
                        $due = new \DateTime($t['duedate']);
                        $daysOverdue = (int)$now->diff($due)->days;
                    }
                }

                $tasks[] = [
                    'title'        => $t['task_title'],
                    'status'       => $t['task_status'],
                    'stack'        => $t['stack_title'],
                    'due'          => $t['duedate'],
                    'category'     => $category,
                    'days_overdue' => $daysOverdue,
                ];
            }
            $result[] = [
                'name'  => $proj['name'],
                'tasks' => $tasks,
            ];
        }
        return $result;
    }

    /**
     * Completion detail: per-project list of completed tasks with completion date.
     */
    private function buildCompletionDetails(array $projectMap): array {
        $result = [];
        foreach ($projectMap as $proj) {
            $completed = [];
            $totalTasks = 0;
            foreach ($proj['tasks'] as $t) {
                if ($t['task_status'] === 'deleted') {
                    continue;
                }
                $totalTasks++;
                if ($t['task_status'] === 'done' && $t['last_modified'] !== null) {
                    $dt = (new \DateTime())->setTimestamp((int)$t['last_modified']);
                    $completed[] = [
                        'title'        => $t['task_title'],
                        'completed_at' => $dt->format('Y-m-d H:i'),
                        'stack'        => $t['stack_title'],
                        'due'          => $t['duedate'],
                    ];
                }
            }
            // Sort by completed_at descending (newest first)
            usort($completed, function ($a, $b) {
                return strcmp($b['completed_at'], $a['completed_at']);
            });

            $result[] = [
                'name'        => $proj['name'],
                'total_tasks' => $totalTasks,
                'completed'   => count($completed),
                'tasks'       => $completed,
            ];
        }
        return $result;
    }
}
