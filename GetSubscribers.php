<?php

require 'Framework/Init.php';
use Framework\Helpers\DynamoDb\PreferencesHelper;

$preferenceHelper = (new PreferencesHelper('cognito-prod'))
    ->filter('email_marketing', ['BOOL' => true])
    ->limit(1500);

$preferencesRecords = $preferenceHelper->query();
$items = $preferencesRecords['Items'];
$batch = 1;
$activeSubscribers = countActiveSubscribers($preferenceHelper, $items);

$lastKey = $preferencesRecords['LastEvaluatedKey'];
while ($lastKey) {
    $preferenceHelper->ExclusiveStartKey($lastKey);
    $preferencesRecords = $preferenceHelper->query();
    $lastKey = $preferencesRecords['LastEvaluatedKey'];

    echo "Batch: $batch - $activeSubscribers Active Subscribers".PHP_EOL;
    ++$batch;
    $activeSubscribers += countActiveSubscribers($preferenceHelper, $preferencesRecords['Items']);
}

function countActiveSubscribers($preferenceHelper, $items){
    $count = 0;
    $activeSubscribers = 0;
    foreach ($items as $item) {
        ++$count;
        try {
            $profile = $preferenceHelper->getItem($item['PK']['S'], 'PROFILE');
            if($profile['enabled']['BOOL'] === true){
                ++$activeSubscribers;
            }
        } catch (Exception $e) {
            // do nothing
        }
        show_status($count, count($items));
    }

    return $activeSubscribers;
}

file_put_contents('activeSubscribers.txt', $activeSubscribers);
echo "Final Total Active Subscribers: $activeSubscribers\n";





