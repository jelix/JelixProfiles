<?php

/**
 * @author      Laurent Jouanneau
 * @copyright   2024 Laurent Jouanneau
 *
 * @see        https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Profiles;

interface ProfilePluginInterface
{

    /**
     * @param array list of aliases  alias=>profile name
     * @param mixed $aliases
     */
    public function setAliases($aliases);

    /**
     * @param array list of options that will be share by other profile of the category
     * @param mixed $common
     */
    public function setCommon($common);

    /**
     * @param array list of options of a profile
     * @param mixed $name
     * @param mixed $profile
     */
    public function addProfile($name, $profile);

    /**
     * Add a list of profiles
     * @param array $profiles
     * @return void
     */
    public function addProfiles(array $profiles);

    /**
     * @param array the array in which analysed profiles should be stored
     * @param mixed $profiles
     */
    public function getProfiles(&$profiles);
}