<?php

$commands = [
    'test' => [
        'description' => 'test command',
        'class' => 'App\Commands\Test',
        'method' => 'run',
        'profile' => 'cognito-prod',
    ],
    'countActiveSubscribers' => [
        'description' => 'Count the number of subscribers',
        'class' => 'App\Commands\CountActiveSubscribers',
        'method' => 'run',
        'profile' => 'cognito-prod',
        'file' => __DIR__.'/../../Storage/activeSubscribers.json'
    ],
];