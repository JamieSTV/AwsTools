<?php

namespace App\Helpers\DynamoDb;

use App\Helpers\DynamoDb\DynamoBase;

class Profile extends DynamoBase
{
    private $tableName = 'ProdViewerStore';
    protected $SK = 'PROFILE';

    public function __construct(string $profile)
    {
        parent::__construct($profile);

        $this->setTableName($this->tableName);
        $this->index('SK-index');
        $this->keyConditionExpression(['sk']);
        $this->expressionAttributes(['SK' => ['S' => $this->SK]]);
    }
}