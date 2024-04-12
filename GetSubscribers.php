<?php
require 'Framework/Init.php';

$dynamoHelper = new DynamoHelper('cognito-prod');

$preferenceParams = [
    'TableName' => 'ProdViewerStore',
    'IndexName' => 'SK-index',
    'KeyConditionExpression' => '#sk = :sk',
    'ExpressionAttributeNames' => [
        '#sk' => 'SK',
    ],
    'FilterExpression' => 'email_marketing = :email_marketing',
    'ExpressionAttributeValues' => [
        ':sk' => ['S' => 'PREFERENCE'],
        ':email_marketing' => ['BOOL' => true],
    ]
];

$preferencesRecords = $dynamoHelper->query($preferenceParams);
$items = $preferencesRecords['Items'];

$activeSubscribers = 0;
$count = 0; 
foreach ($items as $item) {
    ++$count;
    try {
        $profile = $dynamoHelper->getItem($item['PK']['S'], 'PROFILE');
        if($profile['enabled']['BOOL'] === true){
            ++$activeSubscribers;
        }
    } catch (Exception $e) {
        // do nothing
    }
    show_status($count, count($items));
}

$lastKey = $preferencesRecords['LastEvaluatedKey'];
while ($lastKey) {
    $preferenceParams['ExclusiveStartKey'] = $lastKey;
    $result = $dynamoHelper->query($preferenceParams);
    $lastKey = $result['LastEvaluatedKey'];

    $count = 0;
    $items = $result['Items'];
    foreach ($items as $item) {
        ++$count;
        try {
            $profile = $dynamoHelper->getItem($item['PK']['S'], 'PROFILE');
            if($profile['enabled']['BOOL'] === true){
                ++$activeSubscribers;
            }
        } catch (Exception $e) {
            // do nothing
        }
    }

    echo "Active Subscribers: $activeSubscribers\n";

    if($count > 100){
        break;
    }
}

echo "Final Total Active Subscribers: $activeSubscribers\n";





