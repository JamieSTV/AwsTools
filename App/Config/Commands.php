<?php

$commands = [
    'countActiveSubscribers' => [
        'description' => 'Count the number of subscribers',
        'class' => 'App\Commands\CountActiveSubscribers',
        'method' => 'run',
        'profile' => 'cognito-prod',
        'file' => __DIR__.'/../../Storage/activeSubscribers.json'
    ],    
    'getUser' => [
        'description' => 'Get a user by congito id',
        'class' => 'App\Commands\GetUser',
        'method' => 'run',
        'profile' => 'cognito-prod',
        'args' => [
            'cognitoId' => 'required'
        ]
    ],
];