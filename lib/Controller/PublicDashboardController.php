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
use Psr\Log\LoggerInterface;

class PublicDashboardController extends Controller {

    private PublicTokenService $publicTokenService;
    private KpiService $kpiService;
    private DeckService $deckService;
    private LoggerInterface $logger;

    public function __construct(
        string $appName,
        IRequest $request,
        PublicTokenService $publicTokenService,
        KpiService $kpiService,
        DeckService $deckService,
        LoggerInterface $logger
    ) {
        parent::__construct($appName, $request);
        $this->publicTokenService = $publicTokenService;
        $this->kpiService = $kpiService;
        $this->deckService = $deckService;
        $this->logger = $logger;
    }

    /**
     * Error boundary: logs any exception server-side and returns a sanitized
     * JSON 500 instead of leaking the exception + stack trace to the client.
     *
     * @param callable(): JSONResponse $handler
     */
    private function guard(callable $handler): JSONResponse {
        try {
            return $handler();
        } catch (\Throwable $e) {
            $this->logger->error('adminpage public request failed: ' . $e->getMessage(), [
                'exception' => $e,
                'app' => 'adminpage',
            ]);
            return new JSONResponse(
                [
                    'error' => 'internal',
                    'message' => 'Something went wrong loading this dashboard. Please try again in a moment.',
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR,
            );
        }
    }

    /**
     * @PublicPage
     * @NoCSRFRequired
     */
    public function getData(string $token): JSONResponse {
        return $this->guard(function () use ($token) {
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
        });
    }
}
