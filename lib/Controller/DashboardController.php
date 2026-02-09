<?php

declare(strict_types=1);

namespace OCA\AdminPage\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class DashboardController extends Controller {

    public function __construct(string $appName, IRequest $request) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return JSONResponse
     */
    public function getData(): JSONResponse {
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
            'alerts' => [
                [
                    'badgeType' => 'action',
                    'badgeLabel' => 'Action required',
                    'description' => '7 invoices overdue more than 60 days',
                ],
                [
                    'badgeType' => 'action',
                    'badgeLabel' => 'Action required',
                    'description' => '2 projects exceeding budget',
                ],
                [
                    'badgeType' => 'attention',
                    'badgeLabel' => 'Attention needed',
                    'description' => '1 project at risk of delay',
                ],
                [
                    'badgeType' => 'ontrack',
                    'badgeLabel' => 'On track',
                    'description' => 'All field teams clocked in today',
                ],
            ],
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
        ];

        return new JSONResponse($data);
    }
}
