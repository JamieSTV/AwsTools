<?php
namespace App\Commands;

use App\Commands\BaseCommand;

class Test extends BaseCommand{

    public function run(){
        dd('Hello World!');
    }

}