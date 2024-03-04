<?php

class pluginsTest extends \PHPUnit\Framework\TestCase
{
    function testPluginAsObject()
    {
        $profile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo');
        $expectedProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo', '_name' => 'default');

        $reader = new \Jelix\Profiles\ProfilesReader(
            array('foo'=> new myReaderPlugin('foo'))
        );
        $profiles = $reader->readFromArray(array(
                                           'foo'=>array(),
                                           'foo:default'=>  $profile
                                       ));

        $profile = $profiles->get('foo');
        $this->assertEquals($expectedProfile, $profile);

        $profile['changeme'] = 'not changed';
        $expectedProfile['changeme'] = 'cool';
        $profiles = $reader->readFromArray(array(
                                               'foo'=>array(),
                                               'foo:default'=>  $profile
                                           ));

        $profile = $profiles->get('foo');
        $this->assertEquals($expectedProfile, $profile);
    }

    function testPluginAsClassName()
    {
        $profile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo');
        $expectedProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo', '_name' => 'default');

        $reader = new \Jelix\Profiles\ProfilesReader(
            array('foo'=> 'myReaderPlugin')
        );
        $profiles = $reader->readFromArray(array(
                                               'foo'=>array(),
                                               'foo:default'=>  $profile
                                           ));

        $profile = $profiles->get('foo');
        $this->assertEquals($expectedProfile, $profile);

        $profile['changeme'] = 'not changed';
        $expectedProfile['changeme'] = 'cool';
        $profiles = $reader->readFromArray(array(
                                               'foo'=>array(),
                                               'foo:default'=>  $profile
                                           ));

        $profile = $profiles->get('foo');
        $this->assertEquals($expectedProfile, $profile);
    }

    function testPluginAsCallback()
    {
        $profile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo');
        $expectedProfile = array(
            'wsdl'=>'books.wsdl', 'option'=>'foo', '_name' => 'default');

        $reader = new \Jelix\Profiles\ProfilesReader(
            function($name) {
                if ($name == 'foo') {
                    return new myReaderPlugin('foo');
                }
                return new \Jelix\Profiles\ReaderPlugin('foo');
            }
        );
        $profiles = $reader->readFromArray(array(
                                               'foo'=>array(),
                                               'foo:default'=>  $profile
                                           ));

        $profile = $profiles->get('foo');
        $this->assertEquals($expectedProfile, $profile);

        $profile['changeme'] = 'not changed';
        $expectedProfile['changeme'] = 'cool';
        $profiles = $reader->readFromArray(array(
                                               'foo'=>array(),
                                               'foo:default'=>  $profile
                                           ));

        $profile = $profiles->get('foo');
        $this->assertEquals($expectedProfile, $profile);
    }



}
