<?php

/**
 * @author Laurent Jouanneau
 * @contributor Yannick Le Guédart, Julien Issler
 * @copyright 2011-2024 Laurent Jouanneau, 2007 Yannick Le Guédart, 2011 Julien Issler
 *
 * @see        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Profiles;

/**
 * Manage the list of profiles and optionally connectors that are using these profiles
 *
 * It contains all profiles and their parameters. It allows to retrieve a specific profile.
 *
 * A profile has a category (for example: database connection), a name, and a list of parameters.
 *
 * ProfilesContainer allows also to manage connectors. Connectors are objects that are using a profile.
 * For example, for a profile that contain access parameters to a database, a connector will be an object allowing
 * to query the corresponding database, and configured using the parameters of the profile.
 *
 * Connectors are provided by the code using ProfilesContainer, or they can be managed directly by ProfilesContainer,
 * via the ProfilesReader plugins implementing the ProfileInstancePluginInterface interface.
 *
 * Connectors objects are stored into an internal pool. Their lifetime is the duration of the process of the http request.
 * So they are managed as singletons, so they are not recreated each time you want to retrieve them from ProfilesContainer.
 */
class ProfilesContainer
{

    /**
     * @var array profiles data
     */
    protected $profiles = array();

    /**
     * pool of objects loaded for profiles.
     *
     * @var object[][]
     */
    protected $objectPool = array();

    /**
     * @var ProfilesReader
     */
    protected $reader;

    /**
     * ProfilesContainer constructor.
     * @param array $profiles profiles data, formated by a ProfilesReader object
     * @param ProfilesReader $profilesReader the profiles reader, to retrieve plugins that can instantiate connectors.
     */
    public function __construct($profiles, ?ProfilesReader $profilesReader = null)
    {
        $this->profiles = $profiles;
        $this->reader = $profilesReader;
    }

    protected function getPlugin($category)
    {
        if ($this->reader) {
            $plugin = $this->reader->getPlugin($category);
        } else {
            $plugin = new ReaderPlugin($category);
        }
        return $plugin;
    }

    /**
     * Gives properties of a profile.
     *
     * A profile is a section in the profiles.ini.php file. Profiles are belong
     * to a category. Each section names is composed by "category:profilename".
     *
     * The given name can be a profile name or an alias of a profile. An alias
     * is a parameter name in the category section of the ini file, and the value
     * of this parameter should be a profile name.
     *
     * @param string $category  the profile category
     * @param string $name      profile name or alias of a profile name. if empty, use the default profile
     * @param bool   $noDefault if true and if the profile doesn't exist, throw an error instead of getting the default profile
     *
     * @throws Exception
     *
     * @return array properties
     */
    public function get($category, $name = '', $noDefault = false)
    {
        if ($name == '') {
            $name = 'default';
        }

        // the name attribute created in this method will be the name of the connection
        // in the connections pool. So profiles of aliases and real profiles should have
        // the same name attribute.

        if (isset($this->profiles[$category][$name])) {
            return $this->profiles[$category][$name];
        }
        // if the profile doesn't exist, we take the default one
        if (!$noDefault && isset($this->profiles[$category]['default'])) {
            return $this->profiles[$category]['default'];
        }

        if ($name == 'default') {
            throw new Exception('No default profile for "'.$category.'"');
        }

        throw new Exception('Unknown profile "'.$name.'" for "'.$category.'"');
    }


    /**
     * Adds a connector into the objects pool, corresponding to a profile.
     *
     * @param string $category the profile category
     * @param string $name     the name of the profile  (value of _name in the retrieved profile)
     * @param object $obj      the connector to store
     * @param mixed  $object
     */
    public function storeConnectorInPool($category, $name, $object)
    {
        $this->objectPool[$category][$name] = $object;
    }

    /**
     * @deprecated use storeConnectorInPool instead
     */
    public function storeInPool($category, $name, $object)
    {
        $this->objectPool[$category][$name] = $object;
    }


    /**
     * Returns the connector that is using the given profile, from the pool managed by ProfilesContainer.
     *
     * The object is supposed to be created by `getConnector`, `getConnectorFromCallback` or previously
     * given to `storeConnectorInPool`.
     *
     * @param string $category the profile category
     * @param string $name     the name of the profile. It should not correspond to a profile alias, so it should
     *                         be the value of _name in the retrieved profile.
     *
     * @return null|object the stored connector. Null if it does not exist.
     */
    public function getConnectorFromPool($category, $name)
    {
        if (isset($this->objectPool[$category][$name])) {
            return $this->objectPool[$category][$name];
        }

        return null;
    }

    /**
     * @deprecated use getConnectorFromPool instead
     */
    public function getFromPool($category, $name)
    {
        return $this->getConnectorFromPool($category, $name);
    }

    /**
     * @deprecated use getConnectorFromCallback instead
     */
    public function getOrStoreInPool($category, $name, $function, $nodefault = false)
    {
        return $this->getConnectorFromCallback($category, $name, $function, $nodefault);
    }

    /**
     * Returns the connector that is using the given profile.
     *
     * If it does not exist yet into the object pool, the connector has to be instantiated by the given callback function.
     *
     * @param string   $category  the profile category
     * @param string   $name      the name of the profile (will be given to get())
     * @param callable $function  the function name called to retrieve the object. It uses call_user_func. The function
     *                            should accept an array containing the profile parameters, and returns an object.
     * @param bool     $noDefault if true and if the profile doesn't exist, throw an error instead of getting the default profile
     *
     * @return null|object the connector, or null if the callback function did not create it.
     */
    public function getConnectorFromCallback($category, $name, $function, $noDefault = false)
    {
        $profile = $this->get($category, $name, $noDefault);
        if (isset($this->objectPool[$category]) && array_key_exists($profile['_name'], $this->objectPool[$category])) {
            return $this->objectPool[$category][$profile['_name']];
        }
        $obj = call_user_func($function, $profile);
        $this->objectPool[$category][$profile['_name']] = $obj;

        return $obj;
    }

    /**
     * Returns the connector that is using the given profile.
     *
     * If it does not exist yet, the object will be created by the ProfilesReader plugin corresponding to the
     * category of the profiles. The plugin must implements the ProfileInstancePluginInterface interface.
     *
     * @param string   $category  the profile category
     * @param string   $name      the name of the profile (will be given to get())
     * @param bool     $noDefault if true and if the profile doesn't exist, throw an error instead of getting the default profile
     *
     * @return null|object the connector object, or null if the plugin cannot create it.
     */
    public function getConnector($category, $name, $noDefault = false)
    {
        $profile = $this->get($category, $name, $noDefault);
        if (isset($this->objectPool[$category]) && array_key_exists($profile['_name'], $this->objectPool[$category])) {
            return $this->objectPool[$category][$profile['_name']];
        }
        $plugin = $this->getPlugin($category);
        if ($plugin instanceof ProfileInstancePluginInterface) {
            $obj = $plugin->getInstanceForPool($name, $profile);
        }
        else {
            $obj = null;
        }
        $this->objectPool[$category][$profile['_name']] = $obj;
        return $obj;
    }


    /**
     * Creates a temporary profile.
     *
     * The lifetime of the profile is the duration of the PHP script.
     *
     * @param string       $category the profile category
     * @param string       $name     the name of the profile
     * @param array|string $params   parameters of the profile. key=parameter name, value=parameter value.
     *                               Or a name of another profile, to create an alias
     *
     * @throws Exception
     */
    public function createVirtualProfile($category, $name, $paramsOrAlias)
    {
        if ($name == '') {
            throw new Exception('The name of a virtual profile for "'.$category.'" is empty');
        }

        $plugin = $this->getPlugin($category);

        if (is_string($paramsOrAlias)) {
            // this is an alias
            if (isset($this->profiles[$category][$paramsOrAlias])) {
                $this->profiles[$category][$name] = $this->profiles[$category][$paramsOrAlias];
            } else {
                throw new Exception('Unknown profile "'.$paramsOrAlias.'" for "'.$category.'"');
            }
        } else {
            // this is a parameters array

            if (isset($this->profiles[$category]['__common__'])) {
                $plugin->setCommon($this->profiles[$category]['__common__']);
            }
            if (isset($this->profiles[$category])) {
                $plugin->addProfiles($this->profiles[$category]);
            }
            $plugin->addProfile($name, $paramsOrAlias);
            $plugin->getProfiles($this->profiles);
        }

        // close existing connection with the same pool name
        if (isset($this->objectPool[$category][$name])) {
            if ($plugin instanceof ProfileInstancePluginInterface) {
                $plugin->closeInstanceForPool($name, $this->objectPool[$category][$name]);
            }
            unset($this->objectPool[$category][$name]);
        }
        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }

    /**
     * Delete all loaded profiles to force to reload the profiles file.
     *
     * WARNING: it destroys all connectors stored in the pool! If they are instantiated by a reader plugins, they will
     * be also "terminated" by the corresponding plugin.
     */
    public function clear()
    {
        // emptying the objectPool array and calling gc_collect_cycles (as it was done in the past), is not always enough.
        // It is more robust to close explicitly connections. This is why `closeInstanceForPool` is called on
        // plugins that implements it.
        foreach($this->objectPool as $category => $connectionObjects) {
            $plugin = $this->getPlugin($category);
            if ($plugin instanceof ProfileInstancePluginInterface) {
                foreach($connectionObjects as $name => $obj) {
                    if ($obj != null) {
                        $plugin->closeInstanceForPool($name, $obj);
                    }
                }
            }
        }

        $this->profiles = null;
        $this->objectPool = array();

        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }
}
