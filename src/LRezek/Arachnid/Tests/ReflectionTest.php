<?php
/**
 * Created By: lrezek
 * Date: 09,08,2014
 */

use LRezek\Arachnid\Meta\Reflection;

class ReflectionTest extends \PHPUnit_Framework_TestCase
{
    function testGetPropertyRegularNotation()
    {
        $name = Reflection::getProperty("getName");

        $this->assertEquals("name", $name);
    }

    function testGetPropertyUnderscoreNotation()
    {
        $name = Reflection::getProperty("get_name");

        $this->assertEquals("name", $name);
    }

    function testGetPropertyWeirdNotation()
    {
        $name = Reflection::getProperty("get_Name");

        $this->assertEquals("name", $name);
    }

    function testGetPropertyWeirdNotation2()
    {
        $name = Reflection::getProperty("getname");

        $this->assertEquals("name", $name);
    }
}

?> 