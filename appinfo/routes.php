<?php

return [
    'routes' => [
        // Authenticated routes
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'dashboard#getData', 'url' => '/api/data', 'verb' => 'GET'],
        ['name' => 'dashboard#getUpcomingTasks', 'url' => '/api/upcoming-tasks', 'verb' => 'GET'],

        // Public dashboard routes
        ['name' => 'public_page#index', 'url' => '/public/{token}', 'verb' => 'GET'],
        ['name' => 'public_dashboard#getData', 'url' => '/api/public/{token}', 'verb' => 'GET'],

        // Admin public-link management routes
        ['name' => 'publicLinks#create', 'url' => '/api/public-links', 'verb' => 'POST'],
        ['name' => 'publicLinks#index', 'url' => '/api/public-links', 'verb' => 'GET'],
        ['name' => 'publicLinks#revoke', 'url' => '/api/public-links/{id}', 'verb' => 'DELETE'],
    ],
];
