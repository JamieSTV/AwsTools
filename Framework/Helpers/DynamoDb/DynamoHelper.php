<?php

namespace Framework\Helpers\DynamoDb;

use Aws\Sts\StsClient;
use Aws\Exception\AwsException;
use Aws\DynamoDb\DynamoDbClient;

class DynamoHelper{
    private $client;
    private $tableName;

    private $params = [];

    public function __construct(string $profile){
        $this->client = $this->getDynamoDbClient($profile);
    }

    public function setTableName(string $tableName){
        $this->tableName = $tableName;
        $this->params = array_merge($this->params, ['TableName' => $tableName]);
        return $this;
    }

    private function getProfile(string $profile):array
    {
        $config = parse_ini_file(getenv('HOME') . '/.aws/config', true);
        return $config['profile '.$profile] ?? [];
    }

    public function getDynamoDbClient(string $profile){
        if($this->client){
            return $this->client;
        }

        $profile = $this->getProfile($profile);
        $region =  $profile['region'];
        $roleArn =  $profile['role_arn'];
        $mfaSerial = $profile['mfa_serial'];

        $mfaToken = readline("Enter MFA Token: ");
        $stsClient = new StsClient([
            'version' => 'latest',
            'region'  => $region,
        ]);
        
        try {
            $result = $stsClient->assumeRole([
                'RoleArn'           => $roleArn,
                'RoleSessionName'   => 'session-name',
                'SerialNumber'      => $mfaSerial,
                'TokenCode'         => $mfaToken,
            ]);

            return new DynamoDbClient([
                'version'     => 'latest',
                'region'      => $region,
                'credentials' => [
                    'key'     => $result['Credentials']['AccessKeyId'],
                    'secret'  => $result['Credentials']['SecretAccessKey'],
                    'token'   => $result['Credentials']['SessionToken'],
                ],
            ]);
        } catch (AwsException $e) {
            echo $e->getMessage();
        }
    }

    public function getItem(string $PK, string $SK){
        $result = $this->client->getItem([
            'TableName' => $this->tableName,
            'Key' => [
                'PK' => ['S' => $PK],
                'SK' => ['S' => $SK]
            ]
        ]);

        return $result['Item'];
    }

    public function query(array $params = null){
        return $this->client->query($params ?? $this->params);
    }
    
    public function limit($limit){
        $this->params['Limit'] = $limit;
        return $this;
    }

    public function filter(string $filterName, array $filter){
        $this->params['FilterExpression'] = "$filterName = :$filterName";
        $this->params['ExpressionAttributeValues'][":$filterName"] = $filter;

        return $this;
    }

    public function expressionAttributes($attributes){
        foreach($attributes as $attributeName => $value){
            $this->params['ExpressionAttributeNames']["#".strtolower($attributeName)] = $attributeName;
            $this->params['ExpressionAttributeValues'][":".strtolower($attributeName)] = $value;
        }

        return $this;
    }
    
    public function ExclusiveStartKey($lastKey){
        $this->params['ExclusiveStartKey'] = $lastKey;
        return $this;
    }

    public function index($indexName){
        $this->params = array_merge($this->params, ['IndexName' => $indexName]);
        return $this;
    }

    public function keyConditionExpression($expressions){
        $parts = [];
        foreach($expressions as $expression){
            $parts[] = "#".strtolower($expression)." = :".strtolower($expression);
        }

        $this->params['KeyConditionExpression'] = implode(' AND ', $parts);
        return $this;
    }
}
