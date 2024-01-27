<?php

/**
 * @author Laurent Jouanneau
 * @copyright 2011-2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Profiles;

class ProfilesReader
{
    /**
     * @var array|callable
     */
    protected $plugins = [];

    /**
     * ProfilesContainer constructor.
     * @param array|callable $plugins
     *          it should be an array containing items like
     *              'type of profile' => 'class name'
     *           or  'type of profile' => object
     *          The classes and object should implement ProfilePluginInterface.
     *        $plugins may be also a function that take a type of profile as
     *        parameter, and returns an object inheriting from ReaderPlugin
     */
    public function __construct($plugins = [])
    {
        $this->plugins = $plugins;
    }

    /**
     * Read all profiles from the source file, and returns
     * consolidated data, processed by plugins.
     *
     * @param string $iniFile path to the file where profiles are stored
     * @param string $cacheFile path to the file where to store some information from profiles
     * @return ProfilesContainer
     */
    public function readFromFile($iniFile, $cacheFile = '')
    {
        if (preg_match('/\\.ini$/', $cacheFile)) {
            // old format
            $cacheFile = str_replace('.ini', '.json', $cacheFile);
        }

        if ($cacheFile != '' && file_exists($cacheFile) && filemtime($iniFile) <= filemtime($cacheFile)) {
            $profiles = json_decode(file_get_contents($cacheFile), JSON_OBJECT_AS_ARRAY);
        } else {
            $sources = parse_ini_file($iniFile, true, INI_SCANNER_TYPED);
            $profiles = $this->compile($sources);
            if ($cacheFile != '') {
                file_put_contents($cacheFile, json_encode($profiles));
            }
        }
        return new ProfilesContainer($profiles, $this);
    }

    /**
     * Read all profiles from the array, and returns
     * consolidated data, processed by plugins.
     *
     * @param array $iniContent ini content as given by parse_ini_* functions
     * @return ProfilesContainer
     */
    public function readFromArray($iniContent)
    {
        $profiles = $this->compile($iniContent);
        return new ProfilesContainer($profiles, $this);
    }

    /**
     * @param array $sources ini content as given by parse_ini_* functions
     * @return array all profiles parameters
     */
    protected function compile(&$sources)
    {
        $plugins = array();
        // sort to be sure to have categories sections and common sections before profiles sections
        ksort($sources);
        foreach ($sources as $name => $profile) {
            if (!is_array($profile)) {
                continue;
            }
            if (strpos($name, ':') === false) {
                // category section: it contains aliases
                if (!isset($plugins[$name])) {
                    $plugins[$name] = $this->getPlugin($name);
                }
                $plugins[$name]->setAliases($profile);
            } else {
                list($category, $pname) = explode(':', $name);
                if (!isset($plugins[$category])) {
                    $plugins[$category] = $this->getPlugin($category);
                }
                if ($pname == '__common__') {
                    $plugins[$category]->setCommon($profile);
                } else {
                    $plugins[$category]->addProfile($pname, $profile);
                }
            }
        }
        $profiles = array();
        foreach ($plugins as $plugin) {
            $plugin->getProfiles($profiles);
        }
        return $profiles;
    }

    /**
     * @param string $name
     * @return ProfilePluginInterface
     */
    public function getPlugin($name)
    {
        if (is_array($this->plugins)) {
            if (!isset($this->plugins[$name])) {
                $this->plugins[$name] = new ReaderPlugin($name);
            } elseif (is_string($this->plugins[$name])) {
                $className = $this->plugins[$name];
                $this->plugins[$name] = new $className($name);
            }
            return $this->plugins[$name];
        }
        if (is_callable($this->plugins)) {
            return call_user_func($this->plugins, $name);
        }
        else {
            return new ReaderPlugin($name);
        }
    }
}
