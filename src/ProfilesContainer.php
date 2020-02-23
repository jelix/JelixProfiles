<?php

/**
 * @author Laurent Jouanneau
 * @contributor Yannick Le Guédart, Julien Issler
 * @copyright 2011-2020 Laurent Jouanneau, 2007 Yannick Le Guédart, 2011 Julien Issler
 *
 * @see        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Profiles;

/**
 *
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
     * @var object[]
     */
    protected $objectPool = array();

    /**
     * @var ProfilesReader
     */
    protected $reader;

    /**
     * ProfilesContainer constructor.
     * @param array $profiles profiles data, formated by a ProfilesReader object
     */
    public function __construct($profiles, ProfilesReader $profilesReader = null)
    {
        $this->profiles = $profiles;
        $this->reader = $profilesReader;
    }

    /**
     * load properties of a profile.
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
        $section = $category.':'.$name;

        // the name attribute created in this method will be the name of the connection
        // in the connections pool. So profiles of aliases and real profiles should have
        // the same name attribute.

        if (isset($this->profiles[$section])) {
            return $this->profiles[$section];
        }
        // if the profile doesn't exist, we take the default one
        if (!$noDefault && isset($this->profiles[$category.':default'])) {
            return $this->profiles[$category.':default'];
        }

        if ($name == 'default') {
            throw new Exception('No default profile for "'.$category.'"');
        }

        throw new Exception('Unknown profile "'.$name.'" for "'.$category.'"');
    }


    /**
     * add an object in the objects pool, corresponding to a profile.
     *
     * @param string $category the profile category
     * @param string $name     the name of the profile  (value of _name in the retrieved profile)
     * @param object $obj      the object to store
     * @param mixed  $object
     */
    public function storeInPool($category, $name, $object)
    {
        $this->objectPool[$category][$name] = $object;
    }

    /**
     * get an object from the objects pool, corresponding to a profile.
     *
     * @param string $category the profile category
     * @param string $name     the name of the profile (value of _name in the retrieved profile)
     *
     * @return null|object the stored object
     */
    public function getFromPool($category, $name)
    {
        if (isset($this->objectPool[$category][$name])) {
            return $this->objectPool[$category][$name];
        }

        return null;
    }

    /**
     * add an object in the objects pool, corresponding to a profile
     * or store the object retrieved from the function, which accepts a profile
     * as parameter (array).
     *
     * @param string   $category  the profile category
     * @param string   $name      the name of the profile (will be given to get())
     * @param callable $function  the function name called to retrieved the object. It uses call_user_func.
     * @param bool     $noDefault if true and if the profile doesn't exist, throw an error instead of getting the default profile
     * @param mixed    $nodefault
     *
     * @return null|object the stored object
     */
    public function getOrStoreInPool($category, $name, $function, $nodefault = false)
    {
        $profile = $this->get($category, $name, $nodefault);
        if (isset($this->objectPool[$category][$profile['_name']])) {
            return $this->objectPool[$category][$profile['_name']];
        }
        $obj = call_user_func($function, $profile);
        if ($obj) {
            $this->objectPool[$category][$profile['_name']] = $obj;
        }

        return $obj;
    }

    /**
     * create a temporary new profile.
     *
     * @param string       $category the profile category
     * @param string       $name     the name of the profile
     * @param array|string $params   parameters of the profile. key=parameter name, value=parameter value.
     *                               we can also indicate a name of an other profile, to create an alias
     *
     * @throws Exception
     */
    public function createVirtualProfile($category, $name, $params)
    {
        if ($name == '') {
            throw new Exception('The name of a virtual profile for "'.$category.'" is empty');
        }

        if (is_string($params)) {
            if (isset($this->profiles[$category.':'.$params])) {
                $this->profiles[$category.':'.$name] = $this->profiles[$category.':'.$params];
            } else {
                throw new Exception('Unknown profile "'.$params.'" for "'.$category.'"');
            }
        } else {
            if ($this->reader) {
                $plugin = $this->reader->getPlugin($category);
            } else {
                $plugin = new ReaderPlugin($category);
            }

            if (isset($this->profiles[$category.':__common__'])) {
                $plugin->setCommon($this->profiles[$category.':__common__']);
            }
            $plugin->addProfile($name, $params);
            $plugin->getProfiles($this->profiles);
        }
        // close existing connection with the same pool name
        unset($this->objectPool[$category][$name]);
        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }

    /**
     * clear the loaded profiles to force to reload the profiles file.
     * WARNING: it destroy all objects stored in the pool!
     */
    public function clear()
    {
        $this->profiles = null;
        $this->objectPool = array();
        if (gc_enabled()) {
            gc_collect_cycles();
        }
    }
}
