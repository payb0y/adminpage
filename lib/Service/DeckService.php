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
     * Fetch all projects for a given organization that have an existing (non-deleted) board.
     *
     * @param int $orgId
     * @return array
     */
    private function fetchProjects(int $orgId): array {
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
            WHERE cp.organization_id = ?
            ORDER BY cp.id
        ";

        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
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
     * Fetch card assignees (user type = 0) for cards belonging to given board IDs.
     *
     * @param int[] $boardIds
     * @return array  card_id => [participant_uid, ...]
     */
    private function fetchCardAssignees(array $boardIds): array {
        if (empty($boardIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));

        $sql = "
            SELECT
                au.card_id,
                au.participant
            FROM *PREFIX*deck_assigned_users au
            JOIN *PREFIX*deck_cards c ON c.id = au.card_id
            JOIN *PREFIX*deck_stacks s ON s.id = c.stack_id
            WHERE s.board_id IN ({$placeholders})
              AND au.type = 0
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
            $map[(int)$row['card_id']][] = $row['participant'];
        }
        return $map;
    }

    /**
     * Build all Project Performance Analytics data from the Deck database.
     *
     * Approach: start from projects → get linked boards → fetch tasks & labels
     * only for those boards. Projects with 0 cards still appear in every widget.
     *
     * @param int $orgId
     * @return array  with keys: projectProgress, memberPerformance,
     *                taskDelayProjects, taskCompletionProjects
     */
    public function getProjectPerformanceData(int $orgId): array {
        // 1. Fetch all projects that have a valid (non-deleted) board
        $projectRows = $this->fetchProjects($orgId);

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

        // 2. Fetch tasks, labels, and assignees scoped to those boards
        $taskRows       = $this->fetchTaskRowsForBoards($boardIds);
        $cardLabels     = $this->fetchCardLabels($boardIds);
        $cardAssignees  = $this->fetchCardAssignees($boardIds);

        // 3. Group tasks into their project by board_id
        foreach ($taskRows as $row) {
            $bid = (int)$row['board_id'];
            if (isset($projectMap[$bid])) {
                $projectMap[$bid]['tasks'][] = $row;
            }
        }

        // 4. Build member performance stats across all tasks
        $members = [];   // participant => { total, done }
        foreach ($taskRows as $row) {
            if ($row['task_status'] === 'deleted') {
                continue;
            }
            $taskId = (int)$row['task_id'];
            $assignees = $cardAssignees[$taskId] ?? [];
            foreach ($assignees as $uid) {
                if (!isset($members[$uid])) {
                    $members[$uid] = ['total' => 0, 'done' => 0];
                }
                $members[$uid]['total']++;
                if ($row['task_status'] === 'done') {
                    $members[$uid]['done']++;
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

        // ─── Widget 2: Member Performance ───
        $memberPerformance = [];
        foreach ($members as $uid => $stats) {
            $progress = $stats['total'] > 0
                ? (int)round(($stats['done'] / $stats['total']) * 100)
                : 0;
            $memberPerformance[] = [
                'name'     => $uid,
                'total'    => $stats['total'],
                'done'     => $stats['done'],
                'progress' => $progress,
            ];
        }
        // Sort by total tasks descending
        usort($memberPerformance, function ($a, $b) {
            return $b['total'] - $a['total'];
        });
        if (empty($memberPerformance)) {
            $memberPerformance[] = [
                'name'     => 'No Assignments',
                'total'    => 0,
                'done'     => 0,
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
            'projectProgress'          => $projectProgress,
            'memberPerformance'        => $memberPerformance,
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
            'memberPerformance' => [
                ['name' => 'No Assignments', 'total' => 0, 'done' => 0, 'progress' => 0],
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
     * @param int $orgId
     * @return array  with keys matching the 4 widgets
     */
    public function getPerformanceDetails(int $orgId): array {
        $projectRows = $this->fetchProjects($orgId);
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

        $taskRows       = $this->fetchTaskRowsForBoards($boardIds);
        $cardLabels     = $this->fetchCardLabels($boardIds);
        $cardAssignees  = $this->fetchCardAssignees($boardIds);

        foreach ($taskRows as $row) {
            $bid = (int)$row['board_id'];
            if (isset($projectMap[$bid])) {
                $projectMap[$bid]['tasks'][] = $row;
            }
        }

        return [
            'progressDetails'   => $this->buildProgressDetails($projectMap),
            'memberDetails'     => $this->buildMemberDetails($taskRows, $cardAssignees, $projectMap),
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
     * Member detail: per-member list of assigned tasks with status.
     */
    private function buildMemberDetails(array $taskRows, array $cardAssignees, array $projectMap): array {
        $members = []; // uid => { total, done, tasks[] }
        foreach ($taskRows as $row) {
            if ($row['task_status'] === 'deleted') {
                continue;
            }
            $taskId = (int)$row['task_id'];
            $assignees = $cardAssignees[$taskId] ?? [];
            foreach ($assignees as $uid) {
                if (!isset($members[$uid])) {
                    $members[$uid] = ['total' => 0, 'done' => 0, 'tasks' => []];
                }
                $members[$uid]['total']++;
                if ($row['task_status'] === 'done') {
                    $members[$uid]['done']++;
                }
                $members[$uid]['tasks'][] = [
                    'title'   => $row['task_title'],
                    'status'  => $row['task_status'],
                    'project' => $row['board_title'],
                    'stack'   => $row['stack_title'],
                    'due'     => $row['duedate'],
                ];
            }
        }

        $result = [];
        foreach ($members as $uid => $data) {
            $result[] = [
                'name'     => $uid,
                'total'    => $data['total'],
                'done'     => $data['done'],
                'progress' => $data['total'] > 0 ? (int)round(($data['done'] / $data['total']) * 100) : 0,
                'tasks'    => $data['tasks'],
            ];
        }
        // Sort by total tasks descending
        usort($result, function ($a, $b) {
            return $b['total'] - $a['total'];
        });
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

    // ═══════════════════════════════════════════════════════════════════════
    // Task Browser – flat list of all tasks with filter metadata
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Return a flat list of every task across all org projects, plus the
     * distinct values for each filterable dimension.
     *
     * @param int $orgId
     * @return array{tasks: array, projects: string[], stacks: string[], labels: string[]}
     */
    public function getTaskBrowser(int $orgId): array {
        $projectRows = $this->fetchProjects($orgId);
        if (empty($projectRows)) {
            return ['tasks' => [], 'projects' => [], 'stacks' => [], 'labels' => []];
        }

        $boardIds   = [];
        $boardToProject = [];  // board_id => project_name
        foreach ($projectRows as $p) {
            $bid = (int)$p['board_id'];
            $boardIds[] = $bid;
            $boardToProject[$bid] = $p['project_name'];
        }

        $taskRows      = $this->fetchTaskRowsForBoards($boardIds);
        $cardLabels    = $this->fetchCardLabels($boardIds);
        $cardAssignees = $this->fetchCardAssignees($boardIds);

        $tasks        = [];
        $projectSet   = [];
        $stackSet     = [];
        $labelSet     = [];

        foreach ($taskRows as $row) {
            if ($row['task_status'] === 'deleted') {
                continue;
            }

            $taskId     = (int)$row['task_id'];
            $bid        = (int)$row['board_id'];
            $project    = $boardToProject[$bid] ?? $row['board_title'];
            $stack      = $row['stack_title'];
            $labels     = $cardLabels[$taskId] ?? [];
            $assignees  = $cardAssignees[$taskId] ?? [];

            $projectSet[$project] = true;
            $stackSet[$stack]     = true;
            foreach ($labels as $l) {
                $labelSet[$l] = true;
            }

            $tasks[] = [
                'id'        => $taskId,
                'title'     => $row['task_title'],
                'project'   => $project,
                'stack'     => $stack,
                'status'    => $row['task_status'],
                'dueBucket' => $row['due_bucket'],
                'due'       => $row['duedate'],
                'labels'    => $labels,
                'assignees' => $assignees,
            ];
        }

        return [
            'tasks'    => $tasks,
            'projects' => array_keys($projectSet),
            'stacks'   => array_keys($stackSet),
            'labels'   => array_keys($labelSet),
        ];
    }

    // ─── PER-PROJECT DETAILS ─────────────────────────────────────────────

    /**
     * Return rich detail data for every project in the org.
     * The frontend picks which project to display via a dropdown.
     */
    public function getProjectDetailsList(int $orgId): array {
        $projects = $this->fetchProjectsWithMeta($orgId);
        if (empty($projects)) {
            return [];
        }

        $projectIds = array_column($projects, 'id');
        $boardIds   = array_map('intval', array_column($projects, 'board_id'));

        $tasksByStack   = $this->fetchTasksByStack($boardIds);
        $dueStats       = $this->fetchDueStats($boardIds);
        $assignees      = $this->fetchAssigneesByBoard($boardIds);
        $timeline       = $this->fetchTimeline($projectIds);
        $resources      = $this->fetchResourceCounts($orgId, $projectIds);
        $completionData = $this->fetchCompletionData($boardIds);

        // Fetch full task list for embedded task browser
        $taskRows      = $this->fetchTaskRowsForBoards($boardIds);
        $cardLabels    = $this->fetchCardLabels($boardIds);
        $cardAssignees = $this->fetchCardAssignees($boardIds);

        // Group tasks by board_id
        $tasksByBoard = [];
        $stackSetByBoard = [];
        $labelSetByBoard = [];
        foreach ($taskRows as $row) {
            if ($row['task_status'] === 'deleted') {
                continue;
            }
            $bid    = (int)$row['board_id'];
            $taskId = (int)$row['task_id'];
            $labels = $cardLabels[$taskId] ?? [];
            $assigneeList = $cardAssignees[$taskId] ?? [];

            foreach ($labels as $l) {
                $labelSetByBoard[$bid][$l] = true;
            }
            $stackSetByBoard[$bid][$row['stack_title']] = true;

            $tasksByBoard[$bid][] = [
                'id'        => $taskId,
                'title'     => $row['task_title'],
                'stack'     => $row['stack_title'],
                'status'    => $row['task_status'],
                'dueBucket' => $row['due_bucket'],
                'due'       => $row['duedate'],
                'createdAt' => !empty($row['card_created_at']) ? date('Y-m-d H:i:s', (int)$row['card_created_at']) : null,
                'labels'    => $labels,
                'assignees' => $assigneeList,
            ];
        }

        $result = [];
        foreach ($projects as $p) {
            $bid = (int)$p['board_id'];
            $pid = (int)$p['id'];

            $stacks = $tasksByStack[$bid] ?? [];
            $totalTasks = 0;
            $doneTasks  = 0;
            foreach ($stacks as $s) {
                $totalTasks += $s['total'];
                if ($s['stack'] === 'Approved/Done') {
                    $doneTasks += $s['total'];
                }
            }
            $doneTasks += $completionData[$bid] ?? 0;

            $result[] = [
                'id'              => $pid,
                'name'            => $p['name'],
                'number'          => $p['number'] ?? '',
                'status'          => (int)$p['status'],
                'statusLabel'     => $this->statusLabel((int)$p['status']),
                'description'     => $p['description'] ?? '',
                'clientName'      => $p['client_name'] ?? '',
                'clientEmail'     => $p['client_email'] ?? '',
                'clientPhone'     => $p['client_phone'] ?? '',
                'location'        => trim(($p['loc_street'] ?? '') . ', ' . ($p['loc_city'] ?? '') . ' ' . ($p['loc_zip'] ?? ''), ', '),
                'createdAt'       => $p['created_at'] ?? '',
                'updatedAt'       => $p['updated_at'] ?? '',
                'totalTasks'      => $totalTasks,
                'doneTasks'       => $doneTasks,
                'completionPct'   => $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0,
                'tasksByStack'    => $stacks,
                'dueStats'        => $dueStats[$bid] ?? ['overdue' => 0, 'today' => 0, 'upcoming' => 0, 'no_due' => 0],
                'assignees'       => $assignees[$bid] ?? [],
                'timeline'        => $timeline[$pid] ?? [],
                'resources'       => $resources[$pid] ?? ['files' => 0, 'whiteboards' => 0, 'notes' => 0],
                'tasks'           => $tasksByBoard[$bid] ?? [],
                'taskStacks'      => array_keys($stackSetByBoard[$bid] ?? []),
                'taskLabels'      => array_keys($labelSetByBoard[$bid] ?? []),
            ];
        }

        return $result;
    }

    private function statusLabel(int $status): string {
        switch ($status) {
            case 0: return 'Active';
            case 1: return 'Waiting on Customer';
            case 2: return 'On Hold';
            case 3: return 'Done';
            default: return 'Unknown';
        }
    }

    private function fetchProjectsWithMeta(int $orgId): array {
        $sql = "
            SELECT cp.id, cp.name, cp.number, cp.type, cp.status, cp.description,
                   cp.client_name, cp.client_email, cp.client_phone,
                   cp.loc_street, cp.loc_city, cp.loc_zip,
                   cp.board_id, cp.folder_id, cp.white_board_id,
                   cp.created_at, cp.updated_at
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
               AND b.deleted_at = 0
            WHERE cp.organization_id = ?
            ORDER BY cp.name
        ";
        $result = $this->db->prepare($sql);
        $result->execute([$orgId]);
        return $result->fetchAll();
    }

    private function fetchTasksByStack(array $boardIds): array {
        if (empty($boardIds)) return [];
        $ph = implode(',', array_fill(0, count($boardIds), '?'));

        $sql = "
            SELECT b.id AS board_id, s.title AS stack, COUNT(c.id) AS total
            FROM *PREFIX*deck_boards b
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            LEFT JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            WHERE b.id IN ($ph)
            GROUP BY b.id, s.title
            ORDER BY b.id, s.title
        ";
        $result = $this->db->prepare($sql);
        $result->execute($boardIds);

        $map = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['board_id']][] = [
                'stack' => $row['stack'],
                'total' => (int)$row['total'],
            ];
        }
        return $map;
    }

    private function fetchDueStats(array $boardIds): array {
        if (empty($boardIds)) return [];
        $ph = implode(',', array_fill(0, count($boardIds), '?'));

        $sql = "
            SELECT b.id AS board_id,
                   SUM(CASE WHEN c.duedate IS NOT NULL AND c.duedate < NOW() THEN 1 ELSE 0 END) AS overdue,
                   SUM(CASE WHEN c.duedate IS NOT NULL AND DATE(c.duedate) = CURDATE() THEN 1 ELSE 0 END) AS today,
                   SUM(CASE WHEN c.duedate IS NOT NULL AND c.duedate > NOW() THEN 1 ELSE 0 END) AS upcoming,
                   SUM(CASE WHEN c.duedate IS NULL THEN 1 ELSE 0 END) AS no_due
            FROM *PREFIX*deck_boards b
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            WHERE b.id IN ($ph)
            GROUP BY b.id
        ";
        $result = $this->db->prepare($sql);
        $result->execute($boardIds);

        $map = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['board_id']] = [
                'overdue'  => (int)$row['overdue'],
                'today'    => (int)$row['today'],
                'upcoming' => (int)$row['upcoming'],
                'no_due'   => (int)$row['no_due'],
            ];
        }
        return $map;
    }

    private function fetchAssigneesByBoard(array $boardIds): array {
        if (empty($boardIds)) return [];
        $ph = implode(',', array_fill(0, count($boardIds), '?'));

        $sql = "
            SELECT b.id AS board_id, dau.participant AS user_id,
                   COUNT(dau.card_id) AS tasks_assigned,
                   SUM(CASE
                       WHEN s.title = 'Approved/Done' OR c.done IS NOT NULL
                       THEN 1 ELSE 0
                   END) AS tasks_done
            FROM *PREFIX*deck_boards b
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            JOIN *PREFIX*deck_assigned_users dau ON dau.card_id = c.id
            WHERE b.id IN ($ph)
            GROUP BY b.id, dau.participant
            ORDER BY tasks_assigned DESC
        ";
        $result = $this->db->prepare($sql);
        $result->execute($boardIds);

        $map = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['board_id']][] = [
                'userId'    => $row['user_id'],
                'tasks'     => (int)$row['tasks_assigned'],
                'doneTasks' => (int)$row['tasks_done'],
            ];
        }
        return $map;
    }

    private function fetchTimeline(array $projectIds): array {
        if (empty($projectIds)) return [];
        $ph = implode(',', array_fill(0, count($projectIds), '?'));

        $sql = "
            SELECT pti.project_id, pti.label, pti.system_key,
                   pti.start_date, pti.end_date, pti.color
            FROM *PREFIX*project_timeline_items pti
            WHERE pti.project_id IN ($ph)
            ORDER BY pti.project_id, pti.order_index
        ";
        $result = $this->db->prepare($sql);
        $result->execute($projectIds);

        $map = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['project_id']][] = [
                'label'     => $row['label'],
                'systemKey' => $row['system_key'],
                'startDate' => $row['start_date'],
                'endDate'   => $row['end_date'],
                'color'     => $row['color'],
            ];
        }
        return $map;
    }

    private function fetchResourceCounts(int $orgId, array $projectIds): array {
        // Fetch per-project resource counts in bulk
        $sql = "
            SELECT cp.id AS project_id,
                SUM(CASE
                    WHEN mt.mimetype NOT IN ('httpd/unix-directory', 'application/vnd.excalidraw+json')
                    THEN 1 ELSE 0
                END) AS files,
                SUM(CASE
                    WHEN mt.mimetype = 'application/vnd.excalidraw+json'
                    THEN 1 ELSE 0
                END) AS whiteboards
            FROM *PREFIX*custom_projects cp
            LEFT JOIN *PREFIX*filecache f ON f.parent = cp.folder_id
            LEFT JOIN *PREFIX*mimetypes mt ON mt.id = f.mimetype
            WHERE cp.organization_id = ? AND cp.id IN (" . implode(',', array_fill(0, count($projectIds), '?')) . ")
            GROUP BY cp.id
        ";
        $params = array_merge([$orgId], $projectIds);
        $result = $this->db->prepare($sql);
        $result->execute($params);

        $map = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['project_id']] = [
                'files'       => (int)$row['files'],
                'whiteboards' => (int)$row['whiteboards'],
                'notes'       => 0,
            ];
        }

        // Notes
        $sqlNotes = "
            SELECT cp.id AS project_id, COUNT(f.fileid) AS cnt
            FROM *PREFIX*custom_projects cp
            LEFT JOIN *PREFIX*filecache pnf ON pnf.parent = cp.folder_id AND pnf.name = 'Public Notes'
            LEFT JOIN *PREFIX*filecache f ON f.parent = pnf.fileid
                AND f.mimetype != (SELECT id FROM *PREFIX*mimetypes WHERE mimetype = 'httpd/unix-directory')
            WHERE cp.organization_id = ? AND cp.id IN (" . implode(',', array_fill(0, count($projectIds), '?')) . ")
            GROUP BY cp.id
        ";
        $result = $this->db->prepare($sqlNotes);
        $result->execute($params);
        while ($row = $result->fetch()) {
            $pid = (int)$row['project_id'];
            if (isset($map[$pid])) {
                $map[$pid]['notes'] = (int)$row['cnt'];
            }
        }

        return $map;
    }

    private function fetchCompletionData(array $boardIds): array {
        if (empty($boardIds)) return [];
        $ph = implode(',', array_fill(0, count($boardIds), '?'));

        // Count cards marked done (c.done IS NOT NULL) that are NOT in the Approved/Done stack
        $sql = "
            SELECT b.id AS board_id,
                   SUM(CASE WHEN c.done IS NOT NULL AND s.title <> 'Approved/Done' THEN 1 ELSE 0 END) AS done_outside
            FROM *PREFIX*deck_boards b
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            JOIN *PREFIX*deck_cards c ON c.stack_id = s.id AND c.deleted_at = 0
            WHERE b.id IN ($ph)
            GROUP BY b.id
        ";
        $result = $this->db->prepare($sql);
        $result->execute($boardIds);

        $map = [];
        while ($row = $result->fetch()) {
            $map[(int)$row['board_id']] = (int)$row['done_outside'];
        }
        return $map;
    }
}
