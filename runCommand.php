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

if(!empty($command['args'])){
    $argsRaw = $argv;
    array_shift($argsRaw);
    array_shift($argsRaw);
    $count = 0;
    foreach($command['args'] as $arg => $type){
        if(!isset($argsRaw[$count])){
            die('Argument '.$arg.' is required'.PHP_EOL);
        }

        $args[$arg] = $argsRaw[$count];
        $count++;
    }
}

$class = new $command['class']($command);

if(!method_exists($class, $command['method'])){
    die('Method not found'.PHP_EOL);
}

if(!empty($command['args'])){
    $class->{$command['method']}($args);
} else {
    $class->{$command['method']}();
}
