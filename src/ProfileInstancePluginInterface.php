<?php

/**
 * @author      Laurent Jouanneau
 * @copyright   2024 Laurent Jouanneau
 *
 * @see         https://jelix.org
 * @licence     GNU Lesser General Public Licence see LICENCE file or http://www.gnu.org/licenses/lgpl.html
 */
namespace Jelix\Profiles;

interface ProfileInstancePluginInterface
{
    /**
     * Creates an object that is using the given profile parameters.
     *
     * @param string $name profile name
     * @param array $profile profile parameter
     * @return object
     * @see ProfilesContainer::getConnector()
     */
    public function getInstanceForPool($name, $profile);


    /**
     * It is responsible to "terminate" the object.
     *
     * For example, if the object maintain a connection to a database, the method should
     * call its method that close the connection.
     *
     * @param string $name
     * @param object $instance the object given by getInstanceForPool
     * @see ProfilesContainer::clear()
     */
    public function closeInstanceForPool($name, $instance);
}