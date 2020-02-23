<?php

/**
 * @author Laurent Jouanneau
 * @copyright 2011-2020 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Profiles;

class ProfilesReader
{
    protected $plugins = array();

    /**
     * ProfilesContainer constructor.
     * @param array $plugins
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
        if ($cacheFile != '' && file_exists($cacheFile) && filemtime($iniFile) <= filemtime($cacheFile)) {
            $sources = parse_ini_file($cacheFile, true, INI_SCANNER_TYPED);
            $profiles = $this->compile($sources);
        } else {
            $sources = parse_ini_file($iniFile, true, INI_SCANNER_TYPED);
            $profiles = $this->compile($sources);
            if ($cacheFile != '') {
                \Jelix\IniFile\Util::write($profiles, $cacheFile);
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
                $plugin = $this->getPlugin($name);
                $plugin->setAliases($profile);
            } else {
                list($category, $pname) = explode(':', $name);
                $plugin = $this->getPlugin($category);
                if ($pname == '__common__') {
                    $plugin->setCommon($profile);
                } else {
                    $plugin->addProfile($pname, $profile);
                }
            }
            $plugins[] = $plugin;
        }
        $profiles = array();
        foreach ($plugins as $plugin) {
            $plugin->getProfiles($profiles);
        }
        return $profiles;
    }

    /**
     * @param string $name
     * @return ReaderPlugin
     */
    public function getPlugin($name)
    {
        if (!isset($this->plugins[$name])) {
            $this->plugins[$name] = new ReaderPlugin($name);
        } elseif (is_string($this->plugins[$name])) {
            $className = $this->plugins[$name];
            $this->plugins[$name] = new $className($name);
        }
        return $this->plugins[$name];
    }
}
