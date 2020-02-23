<?php

use \Jelix\Profiles\ProfilesContainer;

class profilesContainerTest extends \PHPUnit\Framework\TestCase
{
    function testDefaultProfile () {
        $defaultProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo');
        $readedDefaultProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo', '_name' => 'default');

        $reader = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromArray(array(
            'foo'=>array(),
            'foo:default'=>  $defaultProfile
        ));

        $profile = $profiles->get('foo');
        $this->assertEquals($readedDefaultProfile, $profile);

        $profile = $profiles->get('foo','default');
        $this->assertEquals($readedDefaultProfile, $profile);

        $profile = $profiles->get('foo','toto');
        $this->assertEquals($readedDefaultProfile, $profile);

        try {
            $profile = $profiles->get('foo','toto', true);
            $this->fail();
        } catch(\Jelix\Profiles\Exception $e) {
            $this->assertEquals('Unknown profile "toto" for "foo"', $e->getMessage());
        } catch (\Exception $e) {
            $this->fail('bad expected exception');
        }
    }

    function testAliasDefaultProfile () {
        $defaultProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo');
        $readedDefaultProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo', '_name' => 'server1');

        $reader = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromArray(array(
            'foo'=>array('default'=>'server1'),
            'foo:server1'=>  $defaultProfile
        ));

        $profile = $profiles->get('foo');
        $this->assertEquals($readedDefaultProfile, $profile);

        $profile = $profiles->get('foo','default');
        $this->assertEquals($readedDefaultProfile, $profile);

        $profile = $profiles->get('foo','server1');
        $this->assertEquals($readedDefaultProfile, $profile);

        $profile = $profiles->get('foo','toto');
        $this->assertEquals($readedDefaultProfile, $profile);

        try {
            $profile = $profiles->get('foo','toto', true);
            $this->fail();
        } catch(\Jelix\Profiles\Exception $e) {
            $this->assertEquals('Unknown profile "toto" for "foo"', $e->getMessage());
        } catch (Exception $e) {
            $this->fail('bad expected exception');
        }
    }


    function testAliasProfile () {
        $myProfile = array('wsdl'=>'books.wsdl', 'option'=>'foo');
        $readedProfile = array('wsdl'=>'books.wsdl', 'option'=>'foo', '_name'=>'server1');

        $allProfiles = array(
            'foo'=>array('default'=>'server1',
                'myserver'=>'server1'),
            'foo:server1'=>  $myProfile
        );
        $reader = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromArray($allProfiles);

        $profile = $profiles->get('foo');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','default');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','server1');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','myserver');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','toto');
        $this->assertEquals($readedProfile, $profile);

        try {
            $profile = $profiles->get('foo','toto', true);
            $this->fail();
        } catch(\Jelix\Profiles\Exception $e) {
            $this->assertEquals('Unknown profile "toto" for "foo"', $e->getMessage());
        } catch (Exception $e) {
            $this->fail('bad expected exception');
        }
    }

    function testVirtualProfile() {
        $myProfile = array('wsdl'=>'books.wsdl', 'option'=>'foo');
        $readedProfile = array('wsdl'=>'books.wsdl', 'option'=>'foo', '_name'=>'server1');
        $allProfiles = array(
            'foo'=>array('default'=>'server1',
                'myserver'=>'server1'),
            'foo:server1'=>  $myProfile
        );
        $reader = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromArray($allProfiles);


        $profiles->createVirtualProfile('foo', 'myalias', 'server1');

        $profile = $profiles->get('foo','server1');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','myalias');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','default');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','myserver');
        $this->assertEquals($readedProfile, $profile);


        $profiles->createVirtualProfile('foo', 'new', array('bla'=>'ok'));

        $profile = $profiles->get('foo','new');
        $this->assertEquals(array('bla'=>'ok', '_name'=>'new'), $profile);

        $profile = $profiles->get('foo','server1');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','myalias');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','default');
        $this->assertEquals($readedProfile, $profile);

        $profile = $profiles->get('foo','myserver');
        $this->assertEquals($readedProfile, $profile);

    }

    function testPool() {
        $myProfile = array('wsdl'=>'books.wsdl', 'option'=>'foo');
        $readedProfile = array('wsdl'=>'books.wsdl', 'option'=>'foo', '_name'=>'server1');
        $allProfiles = array(
            'foo'=>array('default'=>'server1',
                'myserver'=>'server1'),
            'foo:server1'=>  $myProfile
        );
        $reader = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromArray($allProfiles);

        $this->assertNull($profiles->getFromPool('foo', 'bar'));

        $profiles->storeInPool('foo', 'bar', 'a value');
        $this->assertEquals('a value', $profiles->getFromPool('foo', 'bar'));

        $profiles->clear();
        $this->assertNull($profiles->getFromPool('foo', 'bar'));

    }

    function testGetStorePool() {
        $reader = new \Jelix\Profiles\ProfilesReader();
        $profiles = $reader->readFromArray(array());

        try {
            $this->assertEquals('result:array:foo',
                $profiles->getOrStoreInPool('foo', 'new', array($this, '_getObj')));
            $this->fail();
        } catch(\Jelix\Profiles\Exception $e) {
            $this->assertEquals('Unknown profile "new" for "foo"', $e->getMessage());
        }
        $profiles->createVirtualProfile('foo', 'new', array('bla'=>'ok'));
        $this->assertEquals('result:array:new',
            $profiles->getOrStoreInPool('foo', 'new', array($this, '_getObj')));

        $this->assertEquals('result:array:new', $profiles->getFromPool('foo', 'new'));
    }

    public function _getObj($profile){
        $value = 'result:';
        if (is_array($profile))
            $value.='array:';
        if (isset($profile['_name']))
            $value.= $profile['_name'];
        return $value;
    }

}