<?php


namespace LRezek\Arachnid\Tests;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Transport;
use LRezek\Arachnid\Arachnid;
use LRezek\Arachnid\Configuration;
use LRezek\Arachnid\Meta\Repository;
use LRezek\Arachnid\Proxy\Factory;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    function testObtainDefaultClient()
    {
        $configuration = new Configuration;

        $this->assertEquals(new Client('localhost', 7474), $configuration->getClient());
    }

    function testSpecifyHost()
    {
        $configuration = new Configuration(array(
            'host' => 'example.com',
        ));

        $this->assertEquals(new Client('example.com', 7474), $configuration->getClient());
    }

    function testSpecifyPort()
    {
        $configuration = new Configuration(array(
            'port' => 7575,
        ));

        $this->assertEquals(new Client('localhost', 7575), $configuration->getClient());
    }

    function testObtainDefaultProxyFactory()
    {
        $configuration = new Configuration;

        $this->assertEquals(new Factory, $configuration->getProxyFactory());
    }

    function testObtainDebugProxy()
    {
        $configuration = new Configuration(array(
            'debug' => true,
        ));

        $this->assertEquals(new Factory('/tmp', true), $configuration->getProxyFactory());
    }

    function testOntainDifferentDir()
    {
        $configuration = new Configuration(array(
            'proxy_dir' => '/tmp/foo',
        ));

        $this->assertEquals(new Factory('/tmp/foo', false), $configuration->getProxyFactory());
    }

    function testObtainDefaultMetaRepository()
    {
        $configuration = new Configuration;

        $this->assertEquals(new Repository, $configuration->getMetaRepository());
    }

    function testSpecifyAnnotationReader()
    {
        $reader = new \Doctrine\Common\Annotations\CachedReader(new \Doctrine\Common\Annotations\AnnotationReader, new \Doctrine\Common\Cache\ArrayCache);
        $configuration = new Configuration(array(
            'annotation_reader' => $reader,
        ));

        $this->assertEquals(new Repository($reader), $configuration->getMetaRepository());
    }

    function testSpecifyCurl()
    {
        $configuration = new Configuration(array(
            'host' => 'example.com',
            'transport' => 'curl',
        ));

        $this->assertEquals(new Client(new Transport\Curl('example.com', 7474)), $configuration->getClient());
    }

    function testSpecifyStream()
    {
        $configuration = new Configuration(array(
            'host' => 'example.com',
            'transport' => 'stream',
        ));

        $this->assertEquals(new Client(new Transport\Stream('example.com', 7474)), $configuration->getClient());
    }

    function testSpecifyCredentials()
    {
        $configuration = new Configuration(array(
            'username' => 'foobar',
            'password' => 'baz',
        ));

        $transport = new Transport\Curl;
        $transport->setAuth('foobar', 'baz');
        $this->assertEquals(new Client($transport), $configuration->getClient());
    }

    function testSpecifyDateGenerator()
    {
        $configuration = new Configuration(array(
            'date_generator' => function(){
                $currentDate = new \DateTime("04:08");
                return $currentDate->format('H:i');
            }
        ));

        //Test current date generation
        $class = new \ReflectionClass('LRezek\\Arachnid\\Arachnid');
        $method = $class->getMethod('getCurrentDate');
        $method->setAccessible(true);

        //Get the date
        $arachnid = new Arachnid($configuration);
        $date = $method->invoke($arachnid);

        //Change back to private
        $method->setAccessible(false);

        $this->assertEquals("04:08", $date);

    }

}

