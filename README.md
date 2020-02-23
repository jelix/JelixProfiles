API to manage profiles containing access and credentials data.

A profile contains informations to access to a service. A service could be
a database, a web API. Inside an application, you can have different profiles
for the same type of service. 

JelixProfiles allows to indicates those profiles into an ini file. All credentials
are then stored into a single file. When a component of your application needs 
informations about a profile, it will use the JelixProfiles.

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
- second name is a name of your choice. However two names have a special
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
connection type. An example with JelixDatabase, defining the alias "jacl2_profile' for the
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

Ini files are readed by the @@parse_ini_file@@ function, and so specific syntaxes
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
$cacheFile = '/somewhere/profiles.cache.ini';

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


## Virtual profile

You can use a profile which is not declared into the ini file. So
you can use a connection whose informations are known only during the execution.

A virtual profile must be created before using your component. Use
`createVirtualProfile()` method of the object `ProfilesContainer` and pass
the type name of connection, a name and an array of parameters.

Example:

```php

// let's retrieve a profiles container
$iniFile = '/somewhere/profiles.ini';
$cacheFile = '/somewhere/profiles.cache.ini';
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
...
```

Of course, all parameters defined in a `__common__` profile apply on virtual profiles.


