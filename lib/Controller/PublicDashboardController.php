<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use OCA\AdminPage\Service\DeckService;
use OCA\AdminPage\Service\KpiService;
use OCA\AdminPage\Service\PublicTokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class PublicDashboardController extends Controller {

    private PublicTokenService $publicTokenService;
    private KpiService $kpiService;
    private DeckService $deckService;

    public function __construct(
        string $appName,
        IRequest $request,
        PublicTokenService $publicTokenService,
        KpiService $kpiService,
        DeckService $deckService
    ) {
        parent::__construct($appName, $request);
        $this->publicTokenService = $publicTokenService;
        $this->kpiService = $kpiService;
        $this->deckService = $deckService;
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getData(string $token): JSONResponse {
        $orgId = $this->publicTokenService->validateToken($token);

        if ($orgId === null) {
            return new JSONResponse(
                ['error' => 'Invalid or expired link'],
                Http::STATUS_FORBIDDEN
            );
        }

        $perfData = $this->deckService->getProjectPerformanceData($orgId);

        return new JSONResponse([
            'kpis' => $this->kpiService->getKpis($orgId),
            'projectProgress' => $perfData['projectProgress'],
            'taskDelayProjects' => $perfData['taskDelayProjects'],
            'taskCompletionProjects' => $perfData['taskCompletionProjects'],
            'performanceDetails' => $this->deckService->getPerformanceDetails($orgId),
            'projectDetails' => $this->deckService->getProjectDetailsList($orgId),
        ]);
    }
}
