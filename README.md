**USAGE**

Ensure your aws Config/Credentials files are set up correctly.

From the installation folder, you can run:

    ./php runCommand.php [commandName]  [args...]

to run a command

**Create New Commands**

to create a new command add a command file to app/commands/ and extend the base command.

then create a entry in app/config/commands.php to register your command.

you can use the classes in /app/Helpers/DynamoDB to create dynamoClients

these act a bit like Models and can be used in a way that is intended to feel eloquent-ish

eg:

    $preferencesRecords = (new Preference($this->profile))
    	->filter('email_marketing', ['BOOL' => true])
    	->limit(2000)
    	->query();

This would collect the first 2000 rows with SK = PREFERENCE from dynamo, then filter the records where email_marketing == true

It also handles pagination:

    $lastKey = $preferencesRecords['LastEvaluatedKey']
    $nextPage = $preferenceHelper
    	->ExclusiveStartKey($lastKey)
    	->query();

can fetch single rows by it's PK:

    $PK = 'VIEWER#'.strtoupper($args['cognitoId']);
    $viewerProfile = (new Profile($this->profile))
    	->getItem($PK);

**TODO**

extend getItem() to allow  combining multiple rows with the same PK eg

    $PK = 'VIEWER#'.strtoupper($args['cognitoId']);
    $viewerProfile = (new Profile($this->profile))
        ->with(['PREFERENCES'])
    	->getItem($PK);

Could potentially get both the profile and the preferences for that viewer. 
