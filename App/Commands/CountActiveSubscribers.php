<?php
namespace App\Commands;

use App\Helpers\DynamoDb\Preference;
use App\Commands\BaseCommand;

class CountActiveSubscribers extends BaseCommand{
    private $file;

    public function __construct($config){
        parent::__construct($config);
        $this->file = $this->config['file'];
    }

    public function run(){
        // Get the contents of activeSubscribers.json file
        if(!file_exists($this->file)){
            file_put_contents($this->file, json_encode([
                'count' => 0,
                'lastEvaluatedKey' => null
            ], JSON_PRETTY_PRINT));
        }
        
        $activeSubscribers = json_decode(file_get_contents($this->file), true);

        // initialize the PreferencesHelper
        $preferenceHelper = (new Preference($this->profile))
            ->filter('email_marketing', ['BOOL' => true])
            ->limit(2000);

        // If this is the first time running the script, query the first 1500 records
        if(empty($activeSubscribers['lastEvaluatedKey'])){
            $preferencesRecords = $preferenceHelper->query();
            $items = $preferencesRecords['Items'];
            $activeSubscribers['count'] = $this->countActiveSubscribers($preferenceHelper, $items);
            $activeSubscribers['lastEvaluatedKey'] = $preferencesRecords['LastEvaluatedKey'];
        }

        $batch = 1;

        // If there are more records to query, continue querying
        while (!empty($activeSubscribers['lastEvaluatedKey'])) {
            $preferenceHelper->ExclusiveStartKey($activeSubscribers['lastEvaluatedKey']);

            try{ 
                $preferencesRecords = $preferenceHelper->query();
            } catch(\Exception $e){
                // query failed, save $activeSubscribers to activeSubscribers.json and exit
                file_put_contents($this->file, json_encode($activeSubscribers, JSON_PRETTY_PRINT));
                die('Query failed. Run again to start from where it left off.');
            }

            if(empty($preferencesRecords['Items'])){
                // no more items to process. Save $activeSubscribers to activeSubscribers.json and break
                file_put_contents($this->file, json_encode($activeSubscribers, JSON_PRETTY_PRINT));
                echo 'No more items to process.'.PHP_EOL;
                break;
            }

            if($preferencesRecords['LastEvaluatedKey'] == $activeSubscribers['lastEvaluatedKey']){
                // something weird going on. Save $activeSubscribers to activeSubscribers.json and exit
                file_put_contents($this->file, json_encode($activeSubscribers, JSON_PRETTY_PRINT));
                die('LastEvaluatedKey is the same as the previous one. Something is wrong.');
            }

            $activeSubscribers['lastEvaluatedKey'] = $preferencesRecords['LastEvaluatedKey'];

            echo "Batch: $batch - ".$activeSubscribers['count']." Active Subscribers".PHP_EOL;
            ++$batch;
            $activeSubscribers['count'] += $this->countActiveSubscribers($preferenceHelper, $preferencesRecords['Items']);
            file_put_contents($this->file, json_encode($activeSubscribers, JSON_PRETTY_PRINT));
        }

        // Save the final count to activeSubscribers.json
        file_put_contents($this->file, json_encode($activeSubscribers, JSON_PRETTY_PRINT));
        echo "Final Total Active Subscribers: ".$activeSubscribers['count']."\n";
    }

    // Function fetches the profile for the user and increments the activeSubscribers count if the user is enabled
    protected function countActiveSubscribers($preferenceHelper, $items){
        $count = 0;
        $activeSubscribers = 0;
        foreach ($items as $item) {
            ++$count;
            try {
                $profile = $preferenceHelper->getItem($item['PK']['S'], 'PROFILE');
                if(!empty($profile['enabled']['BOOL']) && $profile['enabled']['BOOL'] === true){
                    ++$activeSubscribers;
                }
            } catch (\Exception $e) {
                // do nothing
            }
            showProgress($count, count($items));
        }

        return $activeSubscribers;  
    } 
}






