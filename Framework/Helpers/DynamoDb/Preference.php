<?php

namespace Framework\Helpers\DynamoDb;

use Framework\Helpers\DynamoDb\DynamoBase;

class Preference extends DynamoBase
{
    private $tableName = 'ProdViewerStore';
    private $SK = 'PREFERENCE';

    public function __construct(string $profile)
    {
        parent::__construct($profile);

        $this->setTableName($this->tableName);
        $this->index('SK-index');
        $this->keyConditionExpression(['sk']);
        $this->expressionAttributes(['SK' => ['S' => $this->SK]]);
    }
}