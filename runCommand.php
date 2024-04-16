<?php
require_once 'App/Init.php';

if(empty($argv[1])){
    die('Command name is required'.PHP_EOL);
}

$command = $argv[1];
if(!isset($commands[$command])){
    die('Command not found'.PHP_EOL);
}

$command = $commands[$command];
$class = new $command['class']($command);
$class->{$command['method']}();
