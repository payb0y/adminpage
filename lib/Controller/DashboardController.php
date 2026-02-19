<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use OCA\AdminPage\Service\AlertService;
use OCA\AdminPage\Service\DeckService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\IURLGenerator;

class DashboardController extends Controller {

    private IClientService $clientService;
    private IURLGenerator $urlGenerator;
    private DeckService $deckService;
    private AlertService $alertService;

    public function __construct(
        string $appName,
        IRequest $request,
        IClientService $clientService,
        IURLGenerator $urlGenerator,
        DeckService $deckService,
        AlertService $alertService
    ) {
        parent::__construct($appName, $request);
        $this->clientService = $clientService;
        $this->urlGenerator = $urlGenerator;
        $this->deckService = $deckService;
        $this->alertService = $alertService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse
     */
    public function getData(): JSONResponse {
        // Fetch live project performance data from Deck database
        $perfData = $this->deckService->getProjectPerformanceData();

        $data = [
            'kpis' => [
                [
                    'id' => 'operational',
                    'title' => 'Operational',
                    'icon' => 'icon-folder',
                    'iconColor' => '#4A90D9',
                    'metrics' => [
                        ['value' => '12', 'label' => 'Active Projects'],
                        ['value' => '3', 'label' => 'Projects Behind Schedule'],
                        ['value' => '14', 'label' => 'Delayed Tasks'],
                    ],
                ],
                [
                    'id' => 'financial',
                    'title' => 'Financial',
                    'icon' => 'icon-quota',
                    'iconColor' => '#2E9E5A',
                    'metrics' => [
                        ['value' => '€148K', 'label' => 'Approved Revenue'],
                        ['value' => '€38K', 'label' => 'Outstanding Invoices'],
                        ['value' => '7', 'label' => 'Invoices Overdue'],
                    ],
                ],
                [
                    'id' => 'commercial',
                    'title' => 'Commercial',
                    'icon' => 'icon-mail',
                    'iconColor' => '#E67E5A',
                    'metrics' => [
                        ['value' => '26', 'label' => 'Quotes Sent'],
                        ['value' => '7', 'label' => 'Unpaid Quotes'],
                        ['value' => '62%', 'label' => 'Conversion Rate'],
                    ],
                ],
            ],
            'alerts' => $this->alertService->getAlerts(),
            'safetyStats' => [
                [
                    'label' => 'Safety Incidents',
                    'sublabel' => 'Last 30 Days',
                    'value' => '3',
                    'unit' => 'incidents',
                    'trend' => '-25% vs previous period',
                    'trendType' => 'positive',
                ],
                [
                    'label' => 'Near Misses Reported',
                    'sublabel' => '',
                    'value' => '6',
                    'unit' => 'cases',
                    'trend' => '',
                    'trendType' => '',
                ],
                [
                    'label' => 'Open Safety Actions',
                    'sublabel' => '',
                    'value' => '4',
                    'unit' => 'actions',
                    'trend' => '',
                    'trendType' => '',
                ],
                [
                    'label' => 'Days Since Last Incident',
                    'sublabel' => '',
                    'value' => '18',
                    'unit' => 'days',
                    'trend' => '',
                    'trendType' => '',
                ],
            ],
            'projectIncidents' => [
                ['name' => 'Multi-Utility Network Installation', 'incidents' => 0],
                ['name' => 'Urban Network Rehabilitation', 'incidents' => 0],
                ['name' => 'Industrial Park Multi-Utility', 'incidents' => 2],
                ['name' => 'Water Supply Renewal – District 5', 'incidents' => 1],
                ['name' => 'Northern Fiber Optic Backbone', 'incidents' => 0],
            ],
            'severityChart' => [
                'labels' => ['Minor', 'Moderate', 'Severe'],
                'data' => [60, 10, 30],
                'colors' => ['#2ec4b6', '#f4a261', '#e63946'],
            ],
            'causes' => [
                'Missing PPE',
                'Unsafe trench conditions',
                'Equipment malfunction',
                'Poor site signaling',
                'Weather-related risks',
            ],

            // ── Project Performance Analytics (live from Deck DB) ──
            'projectProgress' => $perfData['projectProgress'],
            'productivityByDiscipline' => $perfData['productivityByDiscipline'],
            'taskDelayProjects' => $perfData['taskDelayProjects'],
            'taskCompletionProjects' => $perfData['taskCompletionProjects'],
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

            // Forward cookies from the incoming request for authentication
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
}
