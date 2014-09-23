<?php

use LRezek\Arachnid\Arachnid;
use LRezek\Arachnid\Tests\Entity\RelationDifferentMethodTypes;
use Everyman\Neo4j\Cypher\Query as EM_QUERY;


class ProxyTest extends \PHPUnit_Framework_TestCase
{
    private $id;
    private static $arachnid;

    static function setUpBeforeClass()
    {
        self::$arachnid = new Arachnid(array(
            'transport' => 'curl', // or 'stream'
            'host' => 'localhost',
            'port' => 7474,
            'username' => null,
            'password' => null,
            'proxy_dir' => '/tmp',
            'debug' => true, // Force proxy regeneration on each request
            // 'annotation_reader' => ... // Should be a cached instance of the doctrine annotation reader in production
        ));
    }

    static function tearDownAfterClass()
    {
        self::$arachnid = null;
    }

    function setUp()
    {
        //Generate a ID, so nodes can easily be found and deleted after tests
        $this->id = uniqid();
    }

    function tearDown()
    {
        $queryString = "MATCH (n {testId:'$this->id'}) OPTIONAL MATCH (n)-[r]-() DELETE n,r";
        $query = new EM_QUERY(self::$arachnid->getClient(), $queryString);
        $query->getResultSet();
    }

    function testUncreatableDirectory()
    {
        $this->setExpectedException('Exception', 'Proxy Dir is not writable.');

        //Create a new arachnid instance with an uncreatable directory proxy path
        $a = new Arachnid(array(
            'transport' => 'curl', // or 'stream'
            'host' => 'localhost',
            'port' => 7474,
            'username' => null,
            'password' => null,
            'proxy_dir' => '/<>',
            'debug' => true, // Force proxy regeneration on each request
            // 'annotation_reader' => ... // Should be a cached instance of the doctrine annotation reader in production
        ));

        //Do a persist and reload (to generate a proxy)
        $u1 = new \LRezek\Arachnid\Tests\Entity\User2();
        $u1->setFirstName('Lukas');
        $u1->setTestId($this->id);
        $a->persist($u1);
        $a->flush();
        $a->reload($u1);
    }

    function testUnwriteableDirectory()
    {
        $this->setExpectedException('Exception', 'Proxy Dir is not writable.');

        //Create a new arachnid instance with an unwritable proxy path
        $a = new Arachnid(array(
            'transport' => 'curl', // or 'stream'
            'host' => 'localhost',
            'port' => 7474,
            'username' => null,
            'password' => null,
            'proxy_dir' => '/etc',
            'debug' => true, // Force proxy regeneration on each request
            // 'annotation_reader' => ... // Should be a cached instance of the doctrine annotation reader in production
        ));

        //Do a persist and reload (to generate a proxy)
        $u1 = new \LRezek\Arachnid\Tests\Entity\User3();
        $u1->setFirstName('Lukas');
        $u1->setTestId($this->id);
        $a->persist($u1);
        $a->flush();
        $a->reload($u1);

    }

    function testOptionalAndReferenceParameters()
    {
        $rel = new RelationDifferentMethodTypes;
        $u1 = new \LRezek\Arachnid\Tests\Entity\User();
        $u2 = new \LRezek\Arachnid\Tests\Entity\User();
        $u1->setFirstName('Lukas');
        $u1->setTestId($this->id);
        $u2->setFirstName('Bruce');
        $u2->setTestId($this->id);

        $rel->setFrom($u1);
        $rel->setTo($u2);

        self::$arachnid->persist($rel);
        self::$arachnid->flush();

        $u1_proxy = self::$arachnid->reload($u1);
        $u2_proxy = self::$arachnid->reload($u2);
        $rel_proxy = self::$arachnid->reload($rel);

        //I now have 3 proxies.

        //Assert that everything is as expected coming back
        $this->assertEquals('Lukas', $u2_proxy->getFirstName());
        $this->assertEquals('Lukas', $u1_proxy->getFirstName());
        $this->assertEquals(1, $rel_proxy->getExtra());

        //Pass a new node to the optional start
        $rel_proxy->setFrom($u1);
        $this->assertEquals(1, $rel_proxy->getExtra());
        $rel_proxy->setFrom($u1, 2);
        $this->assertEquals(2, $rel_proxy->getExtra());

        //Pass a new node to the by reference end
        $u3 = new \LRezek\Arachnid\Tests\Entity\User();
        $u3->setFirstName('test');
        $rel_proxy->setTo($u3);
        $this->assertEquals('Lukas', $u3->getFirstName());

    }

    function testTypedAndArrayParameters()
    {
        $rel = new RelationDifferentMethodTypes;
        $u1 = new \LRezek\Arachnid\Tests\Entity\User();
        $u2 = new \LRezek\Arachnid\Tests\Entity\User();
        $u1->setFirstName('Lukas');
        $u1->setTestId($this->id);
        $u2->setFirstName('Bruce');
        $u2->setTestId($this->id);

        $rel->set_from($u1);
        $rel->set_to(array($u2, 3));

        self::$arachnid->persist($rel);
        self::$arachnid->flush();

        $u1_proxy = self::$arachnid->reload($u1);
        $u2_proxy = self::$arachnid->reload($u2);
        $rel_proxy = self::$arachnid->reload($rel);

        //I now have 3 proxies.

        //Assert that everything is as expected coming back
        $this->assertEquals('Bruce', $u2_proxy->getFirstName());
        $this->assertEquals('Lukas', $u1_proxy->getFirstName());
        $this->assertEquals(3, $rel_proxy->getExtra());

        //Pass an unmatching node to the start
        $person = new \LRezek\Arachnid\Tests\Entity\Person();
        try
        {
            $rel_proxy->set_from($person);
            $this->fail();
        }
        catch(Exception $e)
        {
            //All good
        }

        //Pass a non-array value to the proxy
        try
        {
            $rel_proxy->set_to($u2);
            $this->fail();
        }
        catch(Exception $e)
        {
            //All good
        }
    }
}

?> 