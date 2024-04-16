<?php

namespace App\Helpers\DynamoDb;

use Aws\Sts\StsClient;
use Aws\Exception\AwsException;
use Aws\DynamoDb\DynamoDbClient;

class DynamoBase{
    private $client;
    private $tableName;
    
    protected $SK = null;

    private $params = [];

    public function __construct(string $profile){
        $this->client = $this->getDynamoDbClient($profile);
    }

    /**
     * Set Table Name
     * sets the table name
     * 
     * @param string $tableName
     * 
     * @return self
     */
    public function setTableName(string $tableName){
        $this->tableName = $tableName;
        $this->params = array_merge($this->params, ['TableName' => $tableName]);
        return $this;
    }

    /**
     * Get Profile
     * fetches the profile from the aws config file
     * 
     * @param string $profile
     * @return array
     */
    private function getProfile(string $profile):array
    {
        $config = parse_ini_file(getenv('HOME') . '/.aws/config', true);
        return $config['profile '.$profile] ?? [];
    }

    /**
     * Get DynamoDb Client
     * asks for MFA token, then assumes to role of the provided profile
     * before initializing the DynamoDb client
     * 
     * @param string $profile
     * @return DynamoDbClient
     */
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

    /**
     * Get Item
     * fetch any row by its PK/SK combination. 
     * 
     * @param string $PK
     * @param string $SK
     * @return array
     */
    public function getItem(string $PK, string $SK = null){
        $result = $this->client->getItem([
            'TableName' => $this->tableName,
            'Key' => [
                'PK' => ['S' => $PK],
                'SK' => ['S' => $SK ?? $this->SK]
            ]
        ]);

        return $result['Item'];
    }

    /**
     * Query
     * fetch multiple rows based on the provided parameters
     * 
     * @param array $params
     * @return array
     */
    public function query(array $params = null){
        return $this->client->query($params ?? $this->params);
    }
    
    /**
     * Limit
     * sets the limit param
     * 
     * @param int $limit
     *
     * @return self
     */
    public function limit($limit){
        $this->params['Limit'] = $limit;
        return $this;
    }

    /**
     * Filter
     * sets the filter expression
     * @param string $filterName
     * @param array $filter
     * 
     * @return self
     */
    public function filter(string $filterName, array $filter){
        $this->params['FilterExpression'] = "$filterName = :$filterName";
        $this->params['ExpressionAttributeValues'][":$filterName"] = $filter;

        return $this;
    }

    /**
     * ExpressionAttributes
     * sets the expression attributes
     * 
     * @param array $attributes
     * 
     * @return self
     */
    public function expressionAttributes(array $attributes){
        foreach($attributes as $attributeName => $value){
            $this->params['ExpressionAttributeNames']["#".strtolower($attributeName)] = $attributeName;
            $this->params['ExpressionAttributeValues'][":".strtolower($attributeName)] = $value;
        }

        return $this;
    }
    
    /**
     * ExclusiveStartKey
     * sets the last evaluated key
     * 
     * @param array $lastKey
     * 
     * @return self
     */
    public function ExclusiveStartKey(array $lastKey){
        $this->params['ExclusiveStartKey'] = $lastKey;
        return $this;
    }

    /**
     * Index
     * sets the index name
     * 
     * @param string $indexName
     * 
     * @return self
     */
    public function index($indexName){
        $this->params = array_merge($this->params, ['IndexName' => $indexName]);
        return $this;
    }

    /**
     * KeyConditionExpression
     * sets the key condition expression
     * 
     * @param array $expressions
     * 
     * @return self
     */
    public function keyConditionExpression($expressions){
        $parts = [];
        foreach($expressions as $expression){
            $parts[] = "#".strtolower($expression)." = :".strtolower($expression);
        }

        $this->params['KeyConditionExpression'] = implode(' AND ', $parts);
        return $this;
    }

    public function getParams(){
        return $this->params;
    }
}
