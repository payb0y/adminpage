<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\IDBConnection;

class FinancialService {

    private IDBConnection $db;

    public function __construct(IDBConnection $db) {
        $this->db = $db;
    }

    /**
     * Build full financial overview data.
     *
     * @return array
     */
    public function getFinancialData(): array {
        return [
            'revenueOverview'          => $this->getRevenueOverview(),
            'organizations'            => $this->getOrganizations(),
            'plans'                    => $this->getPlans(),
            'subscriptionDistribution' => $this->getSubscriptionDistribution(),
        ];
    }

    // ─── Revenue Overview ────────────────────────────────────────────────

    private function getRevenueOverview(): array {
        // MRR from active paid subscriptions
        $sql = "
            SELECT
                COALESCE(SUM(p.price), 0) AS mrr,
                COUNT(CASE WHEN p.price > 0 THEN 1 END) AS paid_subs,
                COUNT(CASE WHEN p.price = 0 OR p.price IS NULL THEN 1 END) AS free_subs,
                COUNT(*) AS total_subs
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.status = 'active'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();

        $mrr = (float)($row['mrr'] ?? 0.0);
        $arr = $mrr * 12;

        // Potential ARR if all orgs were on Pro
        $proPriceSql = "SELECT price FROM *PREFIX*plans WHERE name = 'Pro' LIMIT 1";
        $r2 = $this->db->prepare($proPriceSql);
        $r2->execute();
        $proRow = $r2->fetch();
        $proPrice = (float)($proRow['price'] ?? 9.99);

        $totalOrgs = (int)($row['total_subs'] ?? 0);
        $potentialArr = $totalOrgs * $proPrice * 12;

        // Revenue per plan
        $planRevSql = "
            SELECT p.name AS plan_name, p.price, COUNT(sub.id) AS sub_count,
                   COALESCE(SUM(p.price), 0) AS plan_mrr
            FROM *PREFIX*plans p
            LEFT JOIN *PREFIX*subscriptions sub
                ON sub.plan_id = p.id AND sub.status = 'active'
            GROUP BY p.id, p.name, p.price
            ORDER BY p.price DESC
        ";
        $r3 = $this->db->prepare($planRevSql);
        $r3->execute();
        $revenueByPlan = $r3->fetchAll();

        return [
            'mrr'            => $mrr,
            'arr'            => $arr,
            'potentialArr'   => $potentialArr,
            'paidSubs'       => (int)($row['paid_subs'] ?? 0),
            'freeSubs'       => (int)($row['free_subs'] ?? 0),
            'totalSubs'      => (int)($row['total_subs'] ?? 0),
            'revenueByPlan'  => array_map(function ($r) {
                return [
                    'plan'     => $r['plan_name'],
                    'price'    => (float)$r['price'],
                    'subs'     => (int)$r['sub_count'],
                    'mrr'      => (float)$r['plan_mrr'],
                ];
            }, $revenueByPlan),
        ];
    }

    // ─── Organizations ───────────────────────────────────────────────────

    private function getOrganizations(): array {
        $sql = "
            SELECT
                o.id AS org_id,
                o.name AS org_name,
                o.contact_email,
                o.admin_uid,
                p.name AS plan_name,
                p.price AS plan_price,
                p.max_projects,
                p.max_members,
                ROUND(p.shared_storage_per_project / 1073741824, 1) AS shared_storage_gb,
                ROUND(p.private_storage_per_user / 1073741824, 1) AS private_storage_gb,
                sub.status AS sub_status,
                sub.started_at,
                sub.ended_at,
                (SELECT COUNT(*) FROM *PREFIX*organization_members om WHERE om.organization_id = o.id) AS member_count,
                (SELECT COUNT(*) FROM *PREFIX*custom_projects cp WHERE cp.organization_id = o.id) AS project_count
            FROM *PREFIX*organizations o
            LEFT JOIN *PREFIX*subscriptions sub ON sub.organization_id = o.id
            LEFT JOIN *PREFIX*plans p ON p.id = sub.plan_id
            ORDER BY o.name
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return array_map(function ($row) {
            return [
                'id'               => (int)$row['org_id'],
                'name'             => $row['org_name'],
                'contactEmail'     => $row['contact_email'] ?? '—',
                'adminUid'         => $row['admin_uid'] ?? '—',
                'plan'             => $row['plan_name'] ?? 'No plan',
                'planPrice'        => (float)($row['plan_price'] ?? 0),
                'maxProjects'      => (int)($row['max_projects'] ?? 0),
                'maxMembers'       => (int)($row['max_members'] ?? 0),
                'sharedStorageGb'  => (float)($row['shared_storage_gb'] ?? 0),
                'privateStorageGb' => (float)($row['private_storage_gb'] ?? 0),
                'subStatus'        => $row['sub_status'] ?? 'none',
                'startedAt'        => $row['started_at'] ?? '—',
                'endedAt'          => $row['ended_at'] ?? '—',
                'memberCount'      => (int)($row['member_count'] ?? 0),
                'projectCount'     => (int)($row['project_count'] ?? 0),
            ];
        }, $rows);
    }

    // ─── Plans ───────────────────────────────────────────────────────────

    private function getPlans(): array {
        $sql = "
            SELECT
                p.id,
                p.name,
                p.price,
                p.currency,
                p.max_projects,
                p.max_members,
                p.is_public,
                ROUND(p.shared_storage_per_project / 1073741824, 1) AS shared_gb,
                ROUND(p.private_storage_per_user / 1073741824, 1) AS private_gb,
                (SELECT COUNT(*) FROM *PREFIX*subscriptions sub WHERE sub.plan_id = p.id AND sub.status = 'active') AS active_subs
            FROM *PREFIX*plans p
            ORDER BY p.price ASC, p.name ASC
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        return array_map(function ($row) {
            return [
                'id'          => (int)$row['id'],
                'name'        => $row['name'],
                'price'       => (float)$row['price'],
                'currency'    => $row['currency'],
                'maxProjects' => (int)$row['max_projects'],
                'maxMembers'  => (int)$row['max_members'],
                'isPublic'    => (bool)$row['is_public'],
                'sharedGb'    => (float)$row['shared_gb'],
                'privateGb'   => (float)$row['private_gb'],
                'activeSubs'  => (int)$row['active_subs'],
            ];
        }, $rows);
    }

    // ─── Subscription Distribution (for donut) ──────────────────────────

    private function getSubscriptionDistribution(): array {
        $sql = "
            SELECT p.name AS plan_name, COUNT(sub.id) AS sub_count
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.status = 'active'
            GROUP BY p.id, p.name
            ORDER BY sub_count DESC
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $rows = $result->fetchAll();

        $labels = [];
        $data   = [];
        $colors = ['#4A90D9', '#2E9E5A', '#E67E5A', '#c878c8', '#f4a261'];

        foreach ($rows as $i => $row) {
            $labels[] = $row['plan_name'];
            $data[]   = (int)$row['sub_count'];
        }

        return [
            'labels' => $labels,
            'data'   => $data,
            'colors' => array_slice($colors, 0, count($labels)),
        ];
    }
}
