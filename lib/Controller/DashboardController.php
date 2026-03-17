<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use DateTime;
use OCA\AdminPage\Service\AlertService;
use OCA\AdminPage\Service\DeckService;
use OCA\AdminPage\Service\KpiService;
use OCA\AdminPage\Service\OrgOverviewService;
use OCA\AdminPage\Service\PublicTokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;

class DashboardController extends Controller {

    private IClientService $clientService;
    private IURLGenerator $urlGenerator;
    private DeckService $deckService;
    private AlertService $alertService;
    private KpiService $kpiService;
    private OrgOverviewService $orgOverviewService;
    private PublicTokenService $publicTokenService;
    private IUserSession $userSession;

    public function __construct(
        string $appName,
        IRequest $request,
        IClientService $clientService,
        IURLGenerator $urlGenerator,
        DeckService $deckService,
        AlertService $alertService,
        KpiService $kpiService,
        OrgOverviewService $orgOverviewService,
        PublicTokenService $publicTokenService,
        IUserSession $userSession
    ) {
        parent::__construct($appName, $request);
        $this->clientService = $clientService;
        $this->urlGenerator = $urlGenerator;
        $this->deckService = $deckService;
        $this->alertService = $alertService;
        $this->kpiService = $kpiService;
        $this->orgOverviewService = $orgOverviewService;
        $this->publicTokenService = $publicTokenService;
        $this->userSession = $userSession;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse
     */
    public function getData(): JSONResponse {
        // Resolve current user → org
        $user = $this->userSession->getUser();
        $uid  = $user ? $user->getUID() : '';
        $orgId = $this->orgOverviewService->resolveOrgId($uid);

        // No associated organization → return empty state
        if ($orgId === null) {
            return new JSONResponse([
                'orgOverview' => null,
                'kpis' => [],
                'alerts' => ['summary' => [], 'overdueTasks' => [], 'unassignedTasks' => [], 'noDueDateTasks' => [], 'stalledProjects' => [], 'zeroProgress' => [], 'pendingUpdates' => []],
                'projectProgress' => [],
                'memberPerformance' => [],
                'taskDelayProjects' => [],
                'taskCompletionProjects' => [],
                'performanceDetails' => null,
                'projectDetails' => [],
            ]);
        }

        // All services scoped to the resolved orgId
        $orgOverview = $this->orgOverviewService->getOrgOverview($uid);
        $perfData    = $this->deckService->getProjectPerformanceData($orgId);

        $data = [
            // Organization overview (profile, subscription, members, projects, usage)
            'orgOverview' => $orgOverview,

            // KPI strip (Operational, Subscription, Team)
            'kpis' => $this->kpiService->getKpis($orgId),

            // Alerts (overdue, unassigned, no due date, stalled, zero progress)
            'alerts' => $this->alertService->getAlerts($orgId),

            // Project Performance Analytics (live from Deck DB)
            'projectProgress'          => $perfData['projectProgress'],
            'memberPerformance'        => $perfData['memberPerformance'],
            'taskDelayProjects'        => $perfData['taskDelayProjects'],
            'taskCompletionProjects'   => $perfData['taskCompletionProjects'],

            // Drill-down detail for perf tiles
            'performanceDetails' => $this->deckService->getPerformanceDetails($orgId),

            // Per-project detail data (includes embedded task list)
            'projectDetails' => $this->deckService->getProjectDetailsList($orgId),
        ];

        return new JSONResponse($data);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @param bool $done
     * @return JSONResponse
     */
    public function getUpcomingTasks(bool $done = false): JSONResponse {
        try {
            $relative = "/ocs/v2.php/apps/deck/api/v1.0/overview/upcoming?done=" . ($done ? "true" : "false");
            $url = $this->urlGenerator->getAbsoluteURL($relative);

            $cookies = $this->request->getHeader('Cookie');

            $client = $this->clientService->newClient();
            $response = $client->get($url, [
                'headers' => [
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                    'Cookie' => $cookies,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            return new JSONResponse($data);
        } catch (\Exception $e) {
            return new JSONResponse([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function listPublicLinks(): JSONResponse {
        $uid = $this->userSession->getUser()->getUID();
        $orgId = $this->orgOverviewService->resolveOrgId($uid);

        if ($orgId === null) {
            return new JSONResponse(['error' => 'No organization found'], 404);
        }

        $links = $this->publicTokenService->listTokens($orgId);
        return new JSONResponse($links);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function createPublicLink(): JSONResponse {
        $uid = $this->userSession->getUser()->getUID();
        $orgId = $this->orgOverviewService->resolveOrgId($uid);

        if ($orgId === null) {
            return new JSONResponse(['error' => 'No organization found'], 404);
        }

        $label = $this->request->getParam('label', null);
        $expiresAtStr = $this->request->getParam('expiresAt', null);

        $expiresAt = null;
        if ($expiresAtStr) {
            try {
                $expiresAt = new DateTime($expiresAtStr);
            } catch (\Exception $e) {
                return new JSONResponse(['error' => 'Invalid date format'], 400);
            }
        }

        $link = $this->publicTokenService->createToken($orgId, $uid, $label, $expiresAt);
        return new JSONResponse($link, 201);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function revokePublicLink(int $id): JSONResponse {
        $this->publicTokenService->revokeToken($id);
        return new JSONResponse(['status' => 'revoked']);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function deletePublicLink(int $id): JSONResponse {
        $this->publicTokenService->deleteToken($id);
        return new JSONResponse(['status' => 'deleted']);
    }
}
