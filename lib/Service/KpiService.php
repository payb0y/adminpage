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
     * Build KPI card data for the top strip.
     *
     * @return array  Three KPI cards: Operational, Financial, Commercial
     */
    public function getKpis(): array {
        return [
            $this->getOperationalKpi(),
            $this->getFinancialKpi(),
            $this->getCommercialKpi(),
        ];
    }

    // ─── OPERATIONAL ─────────────────────────────────────────────────────

    private function getOperationalKpi(): array {
        // Active projects = status IN (0, 1) (0=not started, 1=active)
        $activeProjects = $this->countActiveProjects();

        // Projects behind schedule = projects where overdue open tasks exist
        $behindSchedule = $this->countProjectsBehindSchedule();

        // Delayed (overdue) tasks across all project boards
        $delayedTasks = $this->countDelayedTasks();

        return [
            'id'        => 'operational',
            'title'     => 'Operational',
            'icon'      => 'icon-folder',
            'iconColor' => '#4A90D9',
            'metrics'   => [
                ['value' => (string)$activeProjects, 'label' => 'Active Projects'],
                ['value' => (string)$behindSchedule, 'label' => 'Projects Behind Schedule'],
                ['value' => (string)$delayedTasks,   'label' => 'Delayed Tasks'],
            ],
        ];
    }

    private function countActiveProjects(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countProjectsBehindSchedule(): int {
        $sql = "
            SELECT COUNT(DISTINCT cp.id) AS cnt
            FROM *PREFIX*custom_projects cp
            INNER JOIN *PREFIX*deck_boards b
                ON b.id = CAST(cp.board_id AS UNSIGNED)
                AND b.deleted_at = 0
            JOIN *PREFIX*deck_stacks s ON s.board_id = b.id
            JOIN *PREFIX*deck_cards c ON c.stack_id = s.id
            WHERE c.duedate IS NOT NULL
              AND c.duedate < NOW()
              AND c.deleted_at = 0
              AND s.title <> 'Approved/Done'
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countDelayedTasks(): int {
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

    // ─── FINANCIAL ───────────────────────────────────────────────────────

    private function getFinancialKpi(): array {
        // Monthly Recurring Revenue from active paid subscriptions
        $mrr = $this->calculateMRR();

        // Total number of active organizations
        $totalOrgs = $this->countActiveOrganizations();

        // Paid vs Free ratio
        $paidOrgs = $this->countPaidOrganizations();

        return [
            'id'        => 'financial',
            'title'     => 'Financial',
            'icon'      => 'icon-quota',
            'iconColor' => '#2E9E5A',
            'metrics'   => [
                ['value' => '€' . $this->formatCurrency($mrr), 'label' => 'Monthly Revenue'],
                ['value' => (string)$totalOrgs, 'label' => 'Organizations'],
                ['value' => (string)$paidOrgs, 'label' => 'Paid Subscriptions'],
            ],
        ];
    }

    private function calculateMRR(): float {
        $sql = "
            SELECT COALESCE(SUM(p.price), 0) AS total_mrr
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.status = 'active'
              AND p.price > 0
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (float)($row['total_mrr'] ?? 0.0);
    }

    private function countActiveOrganizations(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*organizations";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countPaidOrganizations(): int {
        $sql = "
            SELECT COUNT(*) AS cnt
            FROM *PREFIX*subscriptions sub
            INNER JOIN *PREFIX*plans p ON p.id = sub.plan_id
            WHERE sub.status = 'active'
              AND p.price > 0
        ";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    // ─── COMMERCIAL ──────────────────────────────────────────────────────

    private function getCommercialKpi(): array {
        // Plans info
        $totalPlans = $this->countPlans();
        $publicPlans = $this->countPublicPlans();

        // Total active subscriptions
        $activeSubs = $this->countActiveSubscriptions();

        // Members across all organizations
        $totalMembers = $this->countTotalMembers();

        return [
            'id'        => 'commercial',
            'title'     => 'Commercial',
            'icon'      => 'icon-mail',
            'iconColor' => '#E67E5A',
            'metrics'   => [
                ['value' => (string)$activeSubs, 'label' => 'Active Subscriptions'],
                ['value' => (string)$totalMembers, 'label' => 'Total Members'],
                ['value' => $totalPlans . ' (' . $publicPlans . ' public)', 'label' => 'Plans Available'],
            ],
        ];
    }

    private function countPlans(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*plans";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countPublicPlans(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*plans WHERE is_public = 1";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countActiveSubscriptions(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*subscriptions WHERE status = 'active'";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    private function countTotalMembers(): int {
        $sql = "SELECT COUNT(*) AS cnt FROM *PREFIX*organization_members";
        $result = $this->db->prepare($sql);
        $result->execute();
        $row = $result->fetch();
        return (int)($row['cnt'] ?? 0);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────

    private function formatCurrency(float $amount): string {
        if ($amount >= 1000) {
            return number_format($amount / 1000, 1, '.', '') . 'K';
        }
        return number_format($amount, 2, '.', '');
    }
}
