<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;

/**
 * Returns all data scoped to the organization the logged-in admin belongs to.
 *
 * Look-up order:
 *  1. oc_organizations.admin_uid  = current user UID  (org owner/creator)
 *  2. oc_organization_members.user_id = current UID   (member with admin role)
 *  3. Fallback: null (user is not associated with any org)
 */
class OrgOverviewService {

    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Public entry point
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @param string $uid  The Nextcloud UID of the logged-in admin.
     * @return array|null  null when the user has no associated organization.
     */
    public function getOrgOverview(string $uid): ?array {
        $orgId = $this->resolveOrgId($uid);
        if ($orgId === null) {
            return null;
        }

        return [
            'profile'      => $this->getProfile($orgId),
            'subscription' => $this->getSubscription($orgId),
            'members'      => $this->getMembers($orgId),
            'projects'     => $this->getProjects($orgId),
            'usageSummary' => $this->getUsageSummary($orgId),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Org resolution
    // ─────────────────────────────────────────────────────────────────────

    private function resolveOrgId(string $uid): ?int {
        // 1. Owner
        $sql = "SELECT id FROM *PREFIX*organizations WHERE admin_uid = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$uid]);
        $row = $stmt->fetch();
        if ($row) {
            return (int)$row['id'];
        }

        // 2. Member with admin role
        $sql2 = "SELECT organization_id FROM *PREFIX*organization_members WHERE user_uid = ? LIMIT 1";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute([$uid]);
        $row2 = $stmt2->fetch();
        if ($row2) {
            return (int)$row2['organization_id'];
        }

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Profile
    // ─────────────────────────────────────────────────────────────────────

    private function getProfile(int $orgId): array {
        $sql = "SELECT id, name, contact_email, admin_uid FROM *PREFIX*organizations WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['id' => $orgId, 'name' => 'Unknown', 'contactEmail' => '—', 'adminUid' => '—'];
        }

        return [
            'id'           => (int)$row['id'],
            'name'         => $row['name'],
            'contactEmail' => $row['contact_email'] ?? '—',
            'adminUid'     => $row['admin_uid'] ?? '—',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Subscription + Plan
    // ─────────────────────────────────────────────────────────────────────

    private function getSubscription(int $orgId): array {
        $sql = "
            SELECT
                sub.id          AS sub_id,
                sub.status,
                sub.started_at,
                sub.ended_at,
                p.id            AS plan_id,
                p.name          AS plan_name,
                p.price,
                p.currency,
                p.max_projects,
                p.max_members,
                p.is_public,
                ROUND(p.shared_storage_per_project / 1073741824, 2) AS shared_storage_gb,
                ROUND(p.private_storage_per_user   / 1073741824, 2) AS private_storage_gb
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.organization_id = ?
            ORDER BY sub.started_at DESC
            LIMIT 1
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'status'          => 'none',
                'planName'        => 'No plan',
                'price'           => 0.0,
                'currency'        => 'EUR',
                'maxProjects'     => 0,
                'maxMembers'      => 0,
                'sharedStorageGb' => 0.0,
                'privateStorageGb'=> 0.0,
                'startedAt'       => null,
                'endedAt'         => null,
                'isPublic'        => false,
            ];
        }

        return [
            'status'           => $row['status'],
            'planName'         => $row['plan_name'],
            'price'            => (float)$row['price'],
            'currency'         => $row['currency'] ?? 'EUR',
            'maxProjects'      => (int)$row['max_projects'],
            'maxMembers'       => (int)$row['max_members'],
            'sharedStorageGb'  => (float)$row['shared_storage_gb'],
            'privateStorageGb' => (float)$row['private_storage_gb'],
            'startedAt'        => $row['started_at'],
            'endedAt'          => $row['ended_at'],
            'isPublic'         => (bool)$row['is_public'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Members
    // ─────────────────────────────────────────────────────────────────────

    private function getMembers(int $orgId): array {
        $sql = "
            SELECT om.user_uid, om.role
            FROM *PREFIX*organization_members om
            WHERE om.organization_id = ?
            ORDER BY om.role DESC, om.user_uid ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        $rows = $stmt->fetchAll();

        return array_map(function ($row) {
            return [
                'userId' => $row['user_uid'],
                'role'   => $row['role'] ?? 'member',
            ];
        }, $rows);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Projects with task stats
    // ─────────────────────────────────────────────────────────────────────

    private function getProjects(int $orgId): array {
        // All projects for this org with their linked board_id
        $sql = "
            SELECT cp.id, cp.name, cp.board_id
            FROM *PREFIX*custom_projects cp
            WHERE cp.organization_id = ?
            ORDER BY cp.name
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        $projects = $stmt->fetchAll();

        if (empty($projects)) {
            return [];
        }

        // Collect board_ids for batch querying
        $boardIds = array_filter(array_column($projects, 'board_id'));
        if (empty($boardIds)) {
            return array_map(function ($p) {
                return $this->emptyProject($p);
            }, $projects);
        }

        // Build a single query for task stats across all boards
        $placeholders = implode(',', array_fill(0, count($boardIds), '?'));

        // Total active cards per board
        $totalSql = "
            SELECT s.board_id, COUNT(c.id) AS total
            FROM *PREFIX*deck_stacks s
            INNER JOIN *PREFIX*deck_cards c ON c.stack_id = s.id
            WHERE s.board_id IN ($placeholders)
              AND c.deleted_at = 0 AND c.archived = 0
            GROUP BY s.board_id
        ";
        $stmt = $this->db->prepare($totalSql);
        $stmt->execute($boardIds);
        $totalByBoard = [];
        foreach ($stmt->fetchAll() as $r) {
            $totalByBoard[(int)$r['board_id']] = (int)$r['total'];
        }

        // Done cards (stack title = 'Approved/Done')
        $doneSql = "
            SELECT s.board_id, COUNT(c.id) AS done
            FROM *PREFIX*deck_stacks s
            INNER JOIN *PREFIX*deck_cards c ON c.stack_id = s.id
            WHERE s.board_id IN ($placeholders)
              AND s.title = 'Approved/Done'
              AND c.deleted_at = 0
            GROUP BY s.board_id
        ";
        $stmt2 = $this->db->prepare($doneSql);
        $stmt2->execute($boardIds);
        $doneByBoard = [];
        foreach ($stmt2->fetchAll() as $r) {
            $doneByBoard[(int)$r['board_id']] = (int)$r['done'];
        }

        // Overdue cards (due_date < now, not in done stack)
        $now = time();
        $overdueSql = "
            SELECT s.board_id, COUNT(c.id) AS overdue
            FROM *PREFIX*deck_stacks s
            INNER JOIN *PREFIX*deck_cards c ON c.stack_id = s.id
            WHERE s.board_id IN ($placeholders)
              AND s.title != 'Approved/Done'
              AND c.due_date IS NOT NULL
              AND c.due_date != ''
              AND UNIX_TIMESTAMP(c.due_date) < ?
              AND c.deleted_at = 0 AND c.archived = 0
            GROUP BY s.board_id
        ";
        $stmt3 = $this->db->prepare($overdueSql);
        $stmt3->execute(array_merge($boardIds, [$now]));
        $overdueByBoard = [];
        foreach ($stmt3->fetchAll() as $r) {
            $overdueByBoard[(int)$r['board_id']] = (int)$r['overdue'];
        }

        // Stacks breakdown per board (for pipeline view)
        $stacksSql = "
            SELECT s.board_id, s.title AS stack_title, s.order AS stack_order, COUNT(c.id) AS card_count
            FROM *PREFIX*deck_stacks s
            LEFT JOIN *PREFIX*deck_cards c
                ON c.stack_id = s.id AND c.deleted_at = 0 AND c.archived = 0
            WHERE s.board_id IN ($placeholders)
            GROUP BY s.board_id, s.id, s.title, s.order
            ORDER BY s.board_id, s.order
        ";
        $stmt4 = $this->db->prepare($stacksSql);
        $stmt4->execute($boardIds);
        $stacksByBoard = [];
        foreach ($stmt4->fetchAll() as $r) {
            $bid = (int)$r['board_id'];
            $stacksByBoard[$bid][] = [
                'title' => $r['stack_title'],
                'count' => (int)$r['card_count'],
            ];
        }

        return array_map(function ($p) use ($totalByBoard, $doneByBoard, $overdueByBoard, $stacksByBoard) {
            $bid      = (int)$p['board_id'];
            $total    = $totalByBoard[$bid]  ?? 0;
            $done     = $doneByBoard[$bid]   ?? 0;
            $overdue  = $overdueByBoard[$bid] ?? 0;
            $progress = $total > 0 ? round(($done / $total) * 100) : 0;

            return [
                'id'       => (int)$p['id'],
                'name'     => $p['name'],
                'boardId'  => $bid,
                'total'    => $total,
                'done'     => $done,
                'overdue'  => $overdue,
                'progress' => $progress,
                'stacks'   => $stacksByBoard[$bid] ?? [],
            ];
        }, $projects);
    }

    private function emptyProject(array $p): array {
        return [
            'id'       => (int)$p['id'],
            'name'     => $p['name'],
            'boardId'  => (int)($p['board_id'] ?? 0),
            'total'    => 0,
            'done'     => 0,
            'overdue'  => 0,
            'progress' => 0,
            'stacks'   => [],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Usage summary (aggregated numbers for the header strip)
    // ─────────────────────────────────────────────────────────────────────

    private function getUsageSummary(int $orgId): array {
        $memberCount = 0;
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*organization_members WHERE organization_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orgId]);
        $r = $stmt->fetch();
        $memberCount = (int)($r['cnt'] ?? 0);

        $projectCount = 0;
        $sql2 = "SELECT COUNT(*) AS cnt FROM *PREFIX*custom_projects WHERE organization_id = ?";
        $stmt2 = $this->db->prepare($sql2);
        $stmt2->execute([$orgId]);
        $r2 = $stmt2->fetch();
        $projectCount = (int)($r2['cnt'] ?? 0);

        // Total tasks across all org boards
        $taskSql = "
            SELECT COUNT(c.id) AS total_tasks,
                   SUM(CASE WHEN s.title = 'Approved/Done' AND c.deleted_at = 0 THEN 1 ELSE 0 END) AS done_tasks
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_stacks s  ON s.board_id = CAST(cp.board_id AS UNSIGNED)
            INNER JOIN *PREFIX*deck_cards  c  ON c.stack_id = s.id AND c.deleted_at = 0 AND c.archived = 0
            WHERE cp.organization_id = ?
        ";
        $stmt3 = $this->db->prepare($taskSql);
        $stmt3->execute([$orgId]);
        $r3 = $stmt3->fetch();

        return [
            'memberCount'  => $memberCount,
            'projectCount' => $projectCount,
            'totalTasks'   => (int)($r3['total_tasks'] ?? 0),
            'doneTasks'    => (int)($r3['done_tasks']  ?? 0),
        ];
    }
}
