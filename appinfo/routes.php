<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'dashboard#getData', 'url' => '/api/data', 'verb' => 'GET'],
        ['name' => 'dashboard#getUpcomingTasks', 'url' => '/api/upcoming-tasks', 'verb' => 'GET'],
    ],
];
