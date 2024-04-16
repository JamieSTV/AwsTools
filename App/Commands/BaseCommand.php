<?php
namespace App\Commands;

class BaseCommand {
    protected $profile;
    protected $config;

    public function __construct($config)
    {
        $this->profile = $config['profile'];
        $this->config = $config;
    }
}