<?php

use Aws\Sts\StsClient;
use Aws\Exception\AwsException;
use Aws\DynamoDb\DynamoDbClient;

class DynamoHelper{

    private $region;
    private $roleArn;
    private $mfaSerial;
    private $client;

    public function __construct($profile){
        $config = parse_ini_file(getenv('HOME') . '/.aws/config', true);
        $profile = $config['profile '.$profile];
        $this->region =  $profile['region'];
        $this->roleArn =  $profile['role_arn'];
        $this->mfaSerial = $profile['mfa_serial'];
        $this->client = $this->getDynamoDbClient();
    }

    public function getDynamoDbClient(){
        if($this->client){
            return $this->client;
        }

        $mfaToken = readline("Enter MFA Token: ");

        $stsClient = new StsClient([
            'version' => 'latest',
            'region'  => $this->region,
        ]);
        
        try {
            $result = $stsClient->assumeRole([
                'RoleArn'           => $this->roleArn,
                'RoleSessionName'   => 'session-name',
                'SerialNumber'      => $this->mfaSerial,
                'TokenCode'         => $mfaToken,
            ]);
        
            $credentials = $result['Credentials'];
            return new DynamoDbClient([
                'version'     => 'latest',
                'region'      => $this->region,
                'credentials' => [
                    'key'     => $credentials['AccessKeyId'],
                    'secret'  => $credentials['SecretAccessKey'],
                    'token'   => $credentials['SessionToken'],
                ],
            ]);
        } catch (AwsException $e) {
            echo $e->getMessage();
        }
    }

    public function getAllBySK(string $SK, $lastKey = null, $limit = 1000){
        $params = [
            'TableName' => 'ProdViewerStore',
            'IndexName' => 'SK-index',
            'KeyConditionExpression' => '#sk = :sk',
            'ExpressionAttributeNames' => [
                '#sk' => 'SK',
            ],
            'ExpressionAttributeValues' => [
                ':sk' => ['S' => $SK]
            ],
            'Limit' => $limit,
        ];

        if($lastKey){
            $params['ExclusiveStartKey'] = $lastKey;
        }

        return $this->client->query($params);
    }

    public function getItem(string $PK, string $SK){
        $result = $this->client->getItem([
            'TableName' => 'ProdViewerStore',
            'Key' => [
                'PK' => ['S' => $PK],
                'SK' => ['S' => $SK]
            ]
        ]);

        return $result['Item'];
    }

    public function query($params){
        return $this->client->query($params);
    }
}
