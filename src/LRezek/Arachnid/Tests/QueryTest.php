<?php

namespace LRezek\Arachnid\Tests;
use Everyman\Neo4j\Cypher\Query as EM_QUERY;
use LRezek\Arachnid\Arachnid;
use LRezek\Arachnid\Tests\Entity\FriendsWith;
use LRezek\Arachnid\Tests\Entity\User;
use LRezek\Arachnid\Exception;

class QueryTest extends DatabaseTestCase
{

    function setUp()
    {
        //Generate a ID, so nodes can easily be found and deleted after tests
        $this->id = uniqid();

        //Get entity manager
        $em = $this->getArachnid();

        //Create users
        $p1 = new User();
        $p2 = new User();
        $p3 = new User();
        $p4 = new User();
        $p5 = new User();

        //Write their properties
        $p1->setFirstName("Angelina");
        $p1->setLastName("Jolie");
        $p1->setTestId($this->id);

        $p2->setFirstName("Edward");
        $p2->setLastName("Norton");
        $p2->setTestId($this->id);

        $p3->setFirstName("Mike");
        $p3->setLastName("Tyson");
        $p3->setTestId($this->id);

        $p4->setFirstName("George");
        $p4->setLastName("Clooney");
        $p4->setTestId($this->id);

        $p5->setFirstName("Oprah");
        $p5->setLastName("Winfrey");
        $p5->setTestId($this->id);

        $nodes = array($p1, $p2, $p3, $p4, $p5);

        $year = 1890;

        //Create 20 relations
        $test_rels = array();
        for($i = 0; $i < 5; $i++)
        {
            $test_rels[$i] = array();

            for($j = 0; $j < 5; $j++)
            {
                if($j != $i)
                {
                    $test_rels[$i][$j] = new FriendsWith();
                    $test_rels[$i][$j]->setSince($year++);
                    $test_rels[$i][$j]->setFrom($nodes[$i]);
                    $test_rels[$i][$j]->setTo($nodes[$j]);
                    $em->persist($test_rels[$i][$j]);
                }
            }
        }

        $em->flush();

    }

    function tearDown()
    {
        $id = $this->id;
        $em = $this->getArachnid();

        $queryString = "MATCH (n {testId:'$id'}) OPTIONAL MATCH (n)-[r]-() DELETE n,r";
        $query = new EM_QUERY($em->getClient(), $queryString);
        $result = $query->getResultSet();
    }

    //*****************************************************
    //***** CYPHER TESTS **********************************
    //*****************************************************

    //Node tests
    function testCypherQueryMatchNodeGetList()
    {
        $queryObj = null;
        $timeElapsed = null;
        $paramsArray = null;
        $em = $this->getArachnid();

        $em->registerEvent(Arachnid::QUERY_RUN, function (\Everyman\Neo4j\Cypher\Query $query, $parameters, $time) use (& $queryObj, & $timeElapsed, & $paramsArray) {
            $queryObj = $query;
            $timeElapsed = $time;
            $paramsArray = $parameters;
        });

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 5);
        $this->assertInstanceOf('Everyman\Neo4j\Cypher\Query', $queryObj);
        $this->assertEmpty($paramsArray);
        $this->assertGreaterThan(0, $timeElapsed);

    }
    function testCypherQueryMatchNodeGetOne()
    {
        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"','movie.firstName="Edward"')
            ->end('movie')
            ->getOne();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);
        $this->assertEquals($set->getFirstName(), "Edward");

    }
    function testCypherQueryMatchNodeGetResult()
    {
        $em = $this->getArachnid();

        $p1 = new User();
        $p1->setFirstName("Angelina");
        $p1->setLastName("Pitt");
        $p1->setTestId($this->id);

        $em->persist($p1);
        $em->flush();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"','movie.firstName="Angelina"')
            ->end('movie')
            ->getResult();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 2);

        foreach($set[0] as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Angelina');
        }

        //Remove temp node
        $em->reload($p1);
        $em->remove($p1);
        $em->flush();

    }

    function testCypherQueryMatchNodeGet_List()
    {
        $queryObj = null;
        $timeElapsed = null;
        $paramsArray = null;
        $em = $this->getArachnid();

        $em->registerEvent(Arachnid::QUERY_RUN, function (\Everyman\Neo4j\Cypher\Query $query, $parameters, $time) use (& $queryObj, & $timeElapsed, & $paramsArray) {
            $queryObj = $query;
            $timeElapsed = $time;
            $paramsArray = $parameters;
        });

        $t = microtime(true);

        $set = $em->create_cypher_query()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->get_list();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 5);
        $this->assertInstanceOf('Everyman\Neo4j\Cypher\Query', $queryObj);
        $this->assertEmpty($paramsArray);
        $this->assertGreaterThan(0, $timeElapsed);

    }
    function testCypherQueryMatchNodeGet_One()
    {
        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->create_cypher_query()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"','movie.firstName="Edward"')
            ->end('movie')
            ->get_one();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);
        $this->assertEquals($set->getFirstName(), "Edward");

    }
    function testCypherQueryMatchNodeGet_Result()
    {
        $em = $this->getArachnid();

        $p1 = new User();
        $p1->setFirstName("Angelina");
        $p1->setLastName("Pitt");
        $p1->setTestId($this->id);

        $em->persist($p1);
        $em->flush();

        $t = microtime(true);

        $set = $em->create_cypher_query()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"','movie.firstName="Angelina"')
            ->end('movie')
            ->get_result();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 2);

        foreach($set[0] as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Angelina');
        }

        //Remove temp node
        $em->reload($p1);
        $em->remove($p1);
        $em->flush();

    }

    function testCypherQueryStartNodeGetList()
    {
        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->start('movie=node(*)')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 5);

    }
    function testCypherQueryStartWithNodeGetList() {

        $em = $this->getArachnid();

        //Grab a node
        $user = $em->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Edward');

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->startWithNode('movie', $user)
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);

        foreach($set as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Edward');
        }

    }
    function testCypherQueryStart_With_NodeGetList() {

        $em = $this->getArachnid();

        //Grab a node
        $user = $em->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Edward');

        $t = microtime(true);

        $set = $em->create_cypher_query()
            ->start_with_node('movie', $user)
            ->end('movie')
            ->get_list();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);

        foreach($set as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Edward');
        }

    }
    function testCypherQueryOrder() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"')
            ->order('movie.firstName DESC')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 5);
        $this->assertEquals($set->first()->getFirstName(), 'Oprah');
        $this->assertEquals($set->last()->getFirstName(), 'Angelina');
    }
    function testCypherQueryLimit() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"')
            ->limit(3)
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 3);

    }
    function testCypherQueryNoResults() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"','movie.firstName="Jeffery"')
            ->end('movie')
            ->getOne();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 0);

    }
    function testCypherQueryStartWithQuery() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->startWithQuery('movie','LRezek\\Arachnid\\Tests\\Entity\\User', 'firstName:Edward')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);

        foreach($set as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Edward');
        }

    }
    function testCypherQueryStart_With_Query() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->create_cypher_query()
            ->start_with_query('movie','LRezek\\Arachnid\\Tests\\Entity\\User', 'firstName:Edward')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);

        foreach($set as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Edward');
        }

    }
    function testCypherQueryStartWithLookup() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->startWithLookup('movie', 'LRezek\\Arachnid\\Tests\\Entity\\User', 'firstName', 'Edward')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);

        foreach($set as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Edward');
        }

    }
    function testCypherQueryStart_With_Lookup() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->create_cypher_query()
            ->start_with_lookup('movie', 'LRezek\\Arachnid\\Tests\\Entity\\User', 'firstName', 'Edward')
            ->where('movie.testId="'.$this->id.'"')
            ->end('movie')
            ->getList();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);

        foreach($set as $node)
        {
            $this->assertEquals($node->getFirstName(), 'Edward');
        }

    }
    function testCypherQueryValueReturn() {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match('(movie:`LRezek\Arachnid\Tests\Entity\User`)')
            ->where('movie.testId="'.$this->id.'"','movie.firstName="Edward"')
            ->end('movie.firstName')
            ->getOne();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals($set, "Edward");

    }

    // Relation tests
    function testCypherQueryMatchRelationGetOne()
    {

        $em = $this->getArachnid();

        $t = microtime(true);

        $set = $em->createCypherQuery()
            ->match("(n {firstName:'Angelina'})-[r:`LRezek\\Arachnid\\Tests\\Entity\\FriendsWith`]->(m {firstName:'Edward'})")
            ->end('r')
            ->getOne();

        $this->printTime(__FUNCTION__, microtime(true) - $t);

        $this->assertEquals(count($set), 1);
        $this->assertEquals($set->getSince(), "1890");

    }

    function testGarbageQuery()
    {
        $em = $this->getArachnid();

        //Create a garbage query
        $query = $em->createCypherQuery();
        $query->match("hello");

        try
        {
            $query->getOne();
            $this->fail();
        }
        catch(Exception $e)
        {
            //Standard notation
            $this->assertEquals('match hello'.PHP_EOL, $e->getQuery());

            //Underscore notation
            $this->assertEquals('match hello'.PHP_EOL, $e->get_query());
        }
    }



}