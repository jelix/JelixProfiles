<?php

use \Jelix\Profiles\ProfilesContainer;

class profilesIniTest extends \PHPUnit\Framework\TestCase
{
    public function setUp(): void
    {
        if (file_exists(__DIR__.'/temp/test1.cache.ini')) {
            unlink(__DIR__.'/temp/test1.cache.ini');
        }
    }

    function testProfileIni()
    {
        $iniFile =  __DIR__.'/profiles/test1.ini';
        $cacheFile = __DIR__.'/temp/test1.cache.ini';
        $reader   = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromFile(
            $iniFile,
            $cacheFile
        );
        $expectedProfile = array(
            'database'=>"testapp",
            'host'=> "mysql",
            'user'=> "test_user",
            "_name"=>"testapp"
        );

        $this->assertTrue(file_exists($cacheFile));

        $profile = $profiles->get('jdb');
        $this->assertEquals($expectedProfile, $profile);

        $reader   = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromFile(
            $iniFile,
            $cacheFile
        );

        $profile = $profiles->get('jdb', 'default');
        $this->assertEquals($expectedProfile, $profile);

        $profile = $profiles->get('jdb', 'toto');
        $this->assertEquals($expectedProfile, $profile);

        $profile = $profiles->get('jdb', 'jacl_profile');
        $this->assertEquals($expectedProfile, $profile);

        $profile = $profiles->get('jdb', 'other');
        $this->assertEquals($expectedProfile = array(
            'database'=>"users",
            "_name"=>"other"
        ), $profile);

        try {
            $profile = $profiles->get('jdb', 'toto', true);
            $this->fail();
        } catch (\Jelix\Profiles\Exception $e) {
            $this->assertEquals(
                'Unknown profile "toto" for "jdb"',
                $e->getMessage()
            );
        } catch (\Exception $e) {
            $this->fail('bad expected exception');
        }
    }
}
