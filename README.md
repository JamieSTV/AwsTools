### USAGE
Ensure your aws Config/Credentials files are set up correctly. 

the installation folder, you can run:

  ./php runCommand [commandName] [args...]

to run a command

to create a new command add a command file to app/commands/ and extend the base command. 
then create a entry in app/config/commands.php to register your command. 

you can use the classes in /app/Helpers/DynamoDB to create dynamoClients
these act a bit like Models and can be used in a way that is intended to feel elogent-ish
eg.

  // initialize the PreferencesHelper
  $preferenceHelper = (new Preference($this->profile))
      ->filter('email_marketing', ['BOOL' => true])
      ->limit(2000);

  $preferencesRecords = $preferenceHelper->query();

