<?php

declare(strict_types=1);

namespace OCA\AdminPage\Service;

use OCP\App\IAppManager;
use OCP\IDBConnection;
use Psr\Container\ContainerInterface;

/**
 * A project's timeline summary for the public share view.
 *
 * Signed-in viewers read projectcreatoraio's own endpoint
 * (/apps/projectcreatoraio/api/v1/projects/{id}/timeline/summary), which
 * checks their access itself. A share link has no user, so this builds the
 * same object server-side, from the same services, once the link's token
 * has resolved to an organisation and the project is that organisation's.
 *
 * It also carries what the Planning tab loads beside the summary — the card
 * dependencies, the delay analysis and the project's timeline items (the
 * Planning tab's second request, /timeline) — so the Gantt on a share link
 * shows the same rows, arrows and badges.
 *
 * Read-only on purpose: unlike projectcreatoraio's endpoints it neither syncs
 * the calculated schedules back to Deck nor re-writes the system items. The
 * hierarchy itself is not side-effect free — it seeds a project's default
 * phases and default card dependencies when they are missing — so the whole
 * build runs in a transaction that is always rolled back: a share link, which
 * has no user, never writes.
 *
 * projectcreatoraio's classes are looked up at call time, not injected, so
 * adminpage still boots when that app is disabled; the summary is then null.
 */
class ProjectTimelineService {

    private const PROVIDER_APP = 'projectcreatoraio';
    private const PROJECT_MAPPER = 'OCA\\ProjectCreatorAIO\\Db\\ProjectMapper';
    private const PHASE_SERVICE = 'OCA\\ProjectCreatorAIO\\Service\\TimelinePhaseService';
    private const PLANNING_SERVICE = 'OCA\\ProjectCreatorAIO\\Service\\TimelinePlanningService';
    private const IMPACT_SERVICE = 'OCA\\ProjectCreatorAIO\\Service\\TimelineImpactService';
    private const ITEM_MAPPER = 'OCA\\ProjectCreatorAIO\\Db\\TimelineItemMapper';

    private IAppManager $appManager;
    private ContainerInterface $container;
    private IDBConnection $db;

    public function __construct(IAppManager $appManager, ContainerInterface $container, IDBConnection $db) {
        $this->appManager = $appManager;
        $this->container = $container;
        $this->db = $db;
    }

    /**
     * @return array<string, mixed>|null null when the project is not the
     *                                   organisation's, or projectcreatoraio is off
     */
    public function getSummary(int $orgId, int $projectId): ?array {
        if (!$this->appManager->isEnabledForAnyone(self::PROVIDER_APP)
            || !class_exists(self::PROJECT_MAPPER)) {
            return null;
        }

        $project = $this->container->get(self::PROJECT_MAPPER)->find($projectId);
        if ($project === null || (int)$project->getOrganizationId() !== $orgId) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $hierarchy = $this->container->get(self::PHASE_SERVICE)->getProjectPhaseHierarchy($project);
            $phases = $hierarchy['phases'];
            $summary = $this->container->get(self::PLANNING_SERVICE)->buildSummary($project, $phases);
            $summary['phases'] = $phases;
            $summary['dependencies'] = $hierarchy['dependencies'];
            $summary['delayAnalysis'] = $this->container->get(self::IMPACT_SERVICE)->analyzeProjectDelays($project, $phases);
            $summary['items'] = array_map(
                static fn ($item) => $item->jsonSerialize(),
                $this->container->get(self::ITEM_MAPPER)->findByProject($projectId),
            );
        } finally {
            $this->db->rollBack();
        }

        return $summary;
    }
}
