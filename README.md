API to manage access and credentials data

# installation

You can install it from Composer. In your project:

```
composer require "jelix/profiles"
```

# Usage

```php

$iniFile = '/somewhere/profiles.ini.php';
$cacheFile = '/somewhere/profiles.cache.php';

$reader = new \Jelix\Profiles\ProfilesReader($iniFile, $cacheFile);

/** @var \Jelix\Profiles\ProfilesContainer $profiles */
$profiles = $reader->read();


$profile = $profiles->get('jdb', 'foo');

```


