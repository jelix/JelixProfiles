API to manage profiles containing access and credentials data.

A profile contains information to access to a service. A service could be
a database, a web API for axampe. Inside an application, you can have different profiles
for the same type of service. 

JelixProfiles allows to indicate those profiles into an ini file. All credentials
are then stored into a single file. When a component of your application (let's call it a "connector")
needs information about a profile, it will use JelixProfiles.

# installation

You can install it from Composer. In your project:

```
composer require "jelix/profiles"
```


# configuration

Profiles data should be stored into an ini file. This file contains 
connections parameters needed by your application components: SQL and
NoSQL databases, SOAP web services, cache etc.


## profile parameters

Each section correspond to a profile. A profile is a set of parameters of a 
single connection. Sections names are composed of two names, separated by a ":":

- first name is the name of the connection type, (often corresponding to 
  the composant name)
- second name is a name of your choice. However, two names have a special
  meaning: `default` indicates the default profile to use if the profile name
  is not given. And `__common__`, described below. 

The content of the section content connection parameters.

Here is an example for the JelixDatabase component 
(It allows to access to a SQL database):

```ini
[jdb:default]
driver=mysqli
host=localhost
login= mylogin
password=mypassword
```

## Profile alias

You can define some profile alias, i.e. more than one name to a profile. This is
useful for example when two library use a different profile name, but you want
that these libraries use the same connection parameters.

Aliases are defined in a section whose name contains only the name of the
connection type. An example with JelixDatabase, defining the alias "jacl2_profile" for the
default profile:

```ini
[jdb]
jacl2_profile = default

[jdb:default]
driver=mysqli
host=localhost
login= mylogin
password=mypassword
```

An alias should not linked to an other alias.

## Common parameters

It is possible to define parameters that are common to all profiles of the same
type. This avoids repeating them in each profile. To do this, you must declare
them in a special profile, `__common__`.

For example, if all connections to SQL databases must be persistent and
are all on the same server:

```ini
[jdb:__common__]
host=my.server.example.com
persistant=on
```

You can of course redefine these parameters in profiles.


## Using environment variables

Ini files are readed by the `parse_ini_file` function, and so specific syntaxes
are available to indicate values coming from outside the ini file.

- You can indicate PHP constants:

```php
# somewhere in a file included by your index.php
define("SOMETHING", "hello world");
```

In one of your ini file:
```ini
foo = SOMETHING
```

Then foo will have the value `"hello world"`.

- You can indicate environment variables:

```bash
# variables set in the environment of PHP-FPM, PHP-CLI, or Apache (with the PHP module)
MYAPP_MYSQL_LOGIN=admin
MYAPP_MYSQL_PASSWORD=Sup3Rp4ssw0rd!
```

Example, in your ini file, you have to use this syntax `${VARIABLE_NAME}`:

```ini
[jdb:default]
driver=mysqli
login= ${MYAPP_MYSQL_LOGIN}
password= ${MYAPP_MYSQL_PASSWORD}
```

# usage

You should use the `ProfilesReader` object, that will read the ini file, and
store results into a `ProfilesContainer` object. With this object, you will be
able to get data of a profile.

If you have this kind of profiles.ini file:

```ini
[jdb:default]
driver=mysqli
host=localhost
login= mylogin
password=mypassword
```

In the code of the component which uses JelixProfiles:

```php
$iniFile = '/somewhere/profiles.ini';
$cacheFile = '/somewhere/profiles.cache.json';

$reader = new \Jelix\Profiles\ProfilesReader();

/** @var \Jelix\Profiles\ProfilesContainer $profiles */
$profiles = $reader->readFromFile($iniFile, $cacheFile);

// get the profile named 'foo' for the type 'jdb'
$profile = $profiles->get('jdb', 'default');

$myDatabaseConnection = new Database(
    $profile['driver'],
    $profile['host'],
    $profile['login'],
    $profile['password']
);

```

Note that you have to give a cache file to `readFromFile`. It allows JelixProfiles to save extra parameters
computed during the loading of the ini file. 


## Virtual profile

You can use a profile which is not declared into the ini file. So
you can use a connection whose information are known only during the execution.

A virtual profile must be created before using your component. Use
`createVirtualProfile()` method of the object `ProfilesContainer` and pass
the type name of connection, a name and an array of parameters.

Example:

```php

// let's retrieve a profiles container
$iniFile = '/somewhere/profiles.ini';
$cacheFile = '/somewhere/profiles.cache.json';
$reader = new \Jelix\Profiles\ProfilesReader();
/** @var \Jelix\Profiles\ProfilesContainer $profiles */
$profiles = $reader->readFromFile($iniFile, $cacheFile);


// now create our virtual profile
$params = array(
   'driver'=>'mysqli',
   'host'=>'localhost',
   'database'=>'jelix',
   'user'=>'toto',
   'password'=>'blabla',
   'persistent'=>false,
   'force_encoding'=>true
);
$profiles->createVirtualProfile('jdb', 'my_profil', $params);

// somewhere else
$profile = $profiles->get('jdb', 'my_profil');

//...
```

Of course, all parameters defined in a `__common__` profile apply on virtual profiles.


# Using plugins to check parameters

The connector, the object that use a profile, may expect to have specific parameters. It may have to check if parameters are ok
before using them. It even may have to "calculate" some other parameters, depending on values it finds into given parameters.

To avoid to do these check or calculation each time the connector is instantiated, there is a solution into JelixProfiles
to process these things during the first read of the profiles file, then final parameters are stored into the
cache file of profiles. 

You can provide a plugin that will do these checks and calculations for a specific category of profiles.
This is a class which should inherits from `Jelix\Profiles\ReaderPlugin`, and implements the `consolidate` method
which receives the profile parameters as an array, and which returns the completed profile.

Example:

```php
// example of a plugin that process a profile containing access parameters to an SQL database
class myDbPlugin extends \Jelix\Profiles\ReaderPlugin
{

    protected function consolidate($profile)
    {
        $newProfile = $profile;
        // Check that the `host` parameter does exist
        if (!isset($profile['driver']) || $profile['driver'] == '' ||
            !isset($profile['host']) || $profile['host'] == '' ||
            !isset($profile['database']) || $profile['database'] == ''
        ) {
            throw new  \Exception('host or database are missing from the profile');
        }
        
        // check if port is present, if not, let's set a default value
        if (!isset($profile['port']) {
            $profile['port'] = 1234;        
        }

        if ($driver == 'pgsql') {
            // let's generate a connection string
            $newProfile['connectionstr'] = 'host='.$profile['host'].';port='.$profile['port'].'database='.$profile['database'];        
        }

        // here you probably want to do more checks etc, but for the example, it is enough.

        // return a new profile, that is ready to be used by your database connection object without checking
        // parameters or calculate connectionstr, at each http requests, because all these parameters will be stored
        // into a cache by ProfilesReader
        return $newProfile;
    }
}

```

Next, you have to indicate this plugin to ProfilesReader, by giving the list of plugins corresponding to each
category.

```php
$reader = new \Jelix\Profiles\ProfilesReader([
    // category name => plugin class name
    'db' => 'myDbPlugin'
]);
```

Another way, is to provide a callback function which will return the plugin corresponding to the given category.
It is useful if your plugin have a specific constructor, or if the class is loaded in a specific way.

```php


class myDbPlugin extends \Jelix\Profiles\ReaderPlugin
{
    // for the example, let's redefine the constructor to have a different constructor than ReaderPlugin...
    public function __construct() {}
 
    // ...   
}

$reader = new \Jelix\Profiles\ProfilesReader(function($category) {
    if ($category == 'db') {
        return new myDbPlugin();
    }
    return null;
});
```

# Using plugins to instantiate automatically connectors

We saw that you can retrieve a profile, and then you have to give it to your connector object:

```php

$profile = $profiles->get('jdb', 'default');

$myDatabaseConnection = new Database(
    $profile['driver'],
    $profile['host'],
    $profile['login'],
    $profile['password']
);

```

`ProfilesContainer` has a method to instantiate automatically the connector corresponding to the profile, and it
allows also to use the same connector object during all the process of an http request. And your code is less heavy:

```php
$myDatabaseConnection = $profiles->getConnector('jdb', 'default');
```

In order to use this method, the plugin must implement the `Jelix\Profiles\ProfileInstancePluginInterface`. It has
two methods, `getInstanceForPool($name, $profile)` and `closeInstanceForPool($name, $instance)`.

The first one is to instantiate the connector object. It is called by `getConnector`. The second method should
terminate the connector object. For example, if the connector object maintain a connection to a database, 
`closeInstanceForPool()` should call its method that close the connection.

```php
class myDbPlugin extends \Jelix\Profiles\ReaderPlugin implements 
{
    protected function consolidate($profile)
    {
        // ...
    }
    
    public function getInstanceForPool($name, $profile)
    {
        return new Database(
          $profile['driver'],
          $profile['host'],
          $profile['login'],
          $profile['password']
        );
    }
    
    /**
     * @param string $name
     * @param Database $instance
     * @return void
     */
    public function closeInstanceForPool($name, $instance)
    {
        $instance->close()
    }
}
```