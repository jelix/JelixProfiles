<?php

class myInstanceForFooProfile
{
    protected $parameters;

    protected $closed = false;

    function __construct(array $profileParameters)
    {
        $this->parameters = $profileParameters;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function close()
    {
        $this->closed = true;
    }

    public function isClosed()
    {
        return $this->closed;
    }
}


class myReaderPlugin extends \Jelix\Profiles\ReaderPlugin implements \Jelix\Profiles\ProfileInstancePluginInterface
{
    protected function consolidate($profile)
    {
        if (isset($profile['changeme'])) {
            $profile['changeme'] = 'cool';
        }
        return $profile;
    }


    public function getInstanceForPool($name, $profile)
    {
        return new myInstanceForFooProfile($profile);
    }

    public function closeInstanceForPool($name, $instance)
    {
        if ($instance) {
            $instance->close();
        }
    }
}