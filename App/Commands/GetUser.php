<?php
namespace App\Commands;

use App\Helpers\DynamoDb\Profile;
use App\Commands\BaseCommand;

class GetUser extends BaseCommand{

    public function __construct($config){
        parent::__construct($config);
    }

    public function run($args){
        $PK = 'VIEWER#'.strtoupper($args['cognitoId']);
        $profileHelper = new Profile($this->profile);
        
        dd($profileHelper->getItem($PK));
    }
}






