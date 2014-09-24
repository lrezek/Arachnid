<?php

namespace LRezek\Arachnid\Tests;
use Everyman\Neo4j\Cypher\Query as EM_QUERY;
use LRezek\Arachnid\Arachnid;
use LRezek\Arachnid\Tests\Entity\ClassParamTestClass;
use LRezek\Arachnid\Tests\Entity\UserDifferentPropertyFormats;
use org\bovigo\vfs\vfsStream;

class ArachnidTest extends TestLogger
{
    private $id;
    private static $arachnid;
    private static $root;

    static function setUpBeforeClass()
    {
        self::$root = vfsStream::setup('tmp');

        self::$arachnid = new Arachnid(array(
            'transport' => 'curl', // or 'stream'
            'host' => 'localhost',
            'port' => 7474,
            'username' => null,
            'password' => null,
            'proxy_dir' => vfsStream::url('tmp'),
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
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $query->getResultSet();
    }

    //*****************************************************
    //***** FLUSH TESTS ***********************************
    //*****************************************************
    function testNodeFlush()
    {
        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush and track the time
        $t = microtime(true);
        ArachnidTest::$arachnid->persist($usr);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get node with everyman
        $queryString = 'MATCH (movie:`LRezek\Arachnid\Tests\Entity\User` {firstName:"Arnold"}) RETURN movie;';
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Make sure there is an entry
        $this->assertNotNull($result);

        //Check the first name
        foreach ($result as $row)
        {
            $this->assertEquals($row['x']->getProperty('firstName'), "Arnold");
        }

    }
    function testRelationFlush()
    {
        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get the relation with everyman
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        //Make sure there is an entry
        $this->assertNotNull($d);

        $i = 0;

        //Check the first name
        foreach ($d as $row)
        {
            //Check the since property
            $this->assertEquals($row['x']->getProperty("since"), "1989");

            $i++;

        }

        $this->assertEquals(1, $i);
    }
    function testMultiRelationFlush() {

        //Create relations
        $usr1 = new Entity\User;
        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2 = new Entity\User;
        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $usr3 = new Entity\User;
        $usr3->setFirstName('Sylvester');
        $usr3->setLastName('Stallone');
        $usr3->setTestId($this->id);

        //Create relation from David to Lukas
        $relation = new Entity\FriendsWith();
        $relation->setTo($usr1);
        $relation->setFrom($usr2);
        $relation->setSince("1989");

        //Create relation from David to Nicole
        $relation2 = new Entity\FriendsWith();
        $relation2->setTo($usr3);
        $relation2->setFrom($usr2);
        $relation2->setSince("1988");

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($relation);
        ArachnidTest::$arachnid->persist($relation2);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get the first relation with everyman
        $queryString = "MATCH (n)-[r {since:'1989'}]->(m) RETURN r;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        //Make sure there is an entry
        $this->assertNotNull($d);

        $i = 0;

        //Check the first name
        foreach ($d as $row)
        {
            //Check the since property
            $this->assertEquals($row['x']->getProperty("since"), "1989");

            $i++;

        }

        //Get the second relation with everyman
        $queryString = "MATCH (n)-[r {since:'1988'}]->(m) RETURN r;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        //Make sure there is an entry
        $this->assertNotNull($d);

        $i = 0;

        //Check the first name
        foreach ($d as $row)
        {
            //Check the since property
            $this->assertEquals($row['x']->getProperty("since"), "1988");

            $i++;

        }

    }
    function testCustomRepoNodeFlush() {

        $usr = new Entity\UserCustomRepo;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($usr);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));
    }
    function testCustomRepoRelationFlush() {

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWithCustomRepo();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

    }

    function testRelationFlushWithoutStart() {

        $this->setExpectedException('Exception');

        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $relation = new Entity\FriendsWith();
        $relation->setTo($usr);
        $relation->setSince("1989");

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($relation);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));
    }
    function testRelationFlushWithoutEnd() {

        $this->setExpectedException('Exception');

        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $relation = new Entity\FriendsWith();
        $relation->setFrom($usr);
        $relation->setSince("1989");

        ArachnidTest::$arachnid->persist($relation);
        ArachnidTest::$arachnid->flush();
    }

    //*****************************************************
    //***** RELOAD TESTS **********************************
    //*****************************************************
    function testNodeReload()
    {

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush it
        ArachnidTest::$arachnid->persist($usr);
        ArachnidTest::$arachnid->flush();

        //Reload
        $usr = ArachnidTest::$arachnid->reload($usr);

        foreach(class_implements(get_class($usr)) as $key => $val)
        {
            if($val != 'LRezek\\Arachnid\\Proxy\\Entity')
            {
                $this->fail();
            }
        }

        $this->assertEquals("Arnold", $usr->getFirstName());
        $this->assertEquals("Schwarzenegger", $usr->getLastName());
        $this->assertEquals($this->id, $usr->getTestId());

    }
    function testRelationReload()
    {
        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        //Flush it
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Reload
        $rel = ArachnidTest::$arachnid->reload($rel);
        $usr1 = ArachnidTest::$arachnid->reload($usr1);
        $usr2 = ArachnidTest::$arachnid->reload($usr2);

        foreach(class_implements(get_class($rel)) as $key => $val)
        {
            if($val != 'LRezek\\Arachnid\\Proxy\\Entity')
            {
                $this->fail();
            }
        }

        $this->assertEquals("Arnold", $usr1->getFirstName());
        $this->assertEquals("Schwarzenegger", $usr1->getLastName());
        $this->assertEquals($this->id, $usr1->getTestId());

        $this->assertEquals("Sean", $usr2->getFirstName());
        $this->assertEquals("Connery", $usr2->getLastName());
        $this->assertEquals($this->id, $usr2->getTestId());

        $this->assertEquals("1989", $rel->getSince());

        //Try lazy loading
        $from = $rel->getFrom();
        $to = $rel->getTo();

        $this->assertEquals($usr1, $from);
        $this->assertEquals($usr2, $to);

    }
    function testReloadUnsavedNode()
    {
        $this->setExpectedException('Exception');

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $usr = ArachnidTest::$arachnid->reload($usr);
    }
    function testReloadUnsavedRelation()
    {
        $this->setExpectedException('Exception');

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        $rel = ArachnidTest::$arachnid->reload($rel);
    }

    //*****************************************************
    //***** CONSTRUCTION TESTS ****************************
    //*****************************************************
    function testNullConfiguration(){

        $a = new Arachnid(null);
        $a->flush();
    }
    function testGarbageConfiguration() {

        $this->setExpectedException('Exception');
        $a = new Arachnid(7);

    }

    //*****************************************************
    //***** UPDATE TESTS **********************************
    //*****************************************************
    function testNodeUpdate() {

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush and track the time
        ArachnidTest::$arachnid->persist($usr);
        ArachnidTest::$arachnid->flush();

        //Reload the node
        $usr = ArachnidTest::$arachnid->reload($usr);

        //Change the name
        $usr->setFirstName('Arnie');

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($usr);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for the node with everyman
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Arnie', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();


        //Check the node
        $i = 0;
        foreach ($result as $row)
        {
            $i++;
            $this->assertEquals("Arnie", $row['x']->getProperty('firstName'));
            $this->assertEquals("Schwarzenegger", $row['x']->getProperty('lastName'));
            $this->assertEquals($this->id, $row['x']->getProperty('testId'));
        }

        $this->assertEquals(1, $i);

    }
    function testRelationUpdate() {

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Change the since
        $rel = ArachnidTest::$arachnid->reload($rel);
        $rel->setSince('1988');

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for relation with everyman
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1988'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        $i = 0;

        //Check the since
        foreach ($d as $row)
        {
            //Check the since property
            $this->assertEquals("1988", $row['x']->getProperty("since"));

            $i++;

        }

        $this->assertEquals(1, $i);
    }

    function testRelationStartNodePropertyUpdate() {

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Grab the relation and its start
        $rel = ArachnidTest::$arachnid->reload($rel);
        $start = $rel->getFrom();

        //Make sure it's the right node
        $this->assertEquals('Arnold', $start->getFirstName());

        //Change the name
        $start->setFirstName('Arnie');

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for the node with everyman
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Arnie', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Check the node
        $i = 0;
        foreach ($result as $row)
        {
            $i++;
            $this->assertEquals("Arnie", $row['x']->getProperty('firstName'));
            $this->assertEquals("Schwarzenegger", $row['x']->getProperty('lastName'));
            $this->assertEquals($this->id, $row['x']->getProperty('testId'));
        }

        $this->assertEquals(1, $i);

        //Make sure there are no arnold nodes
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Arnold', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Check the node
        foreach ($result as $row)
        {
            $this->fail();
        }
    }
    function testRelationEndNodePropertyUpdate() {

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Grab the relation and its start
        $rel = ArachnidTest::$arachnid->reload($rel);
        $end = $rel->getTo();

        //Make sure it's the right node
        $this->assertEquals('Sean', $end->getFirstName());

        //Change the name
        $end->setFirstName('Thomas Sean');

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for the node with everyman
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Thomas Sean', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Check the node
        $i = 0;
        foreach ($result as $row)
        {
            $i++;
            $this->assertEquals("Thomas Sean", $row['x']->getProperty('firstName'));
            $this->assertEquals("Connery", $row['x']->getProperty('lastName'));
            $this->assertEquals($this->id, $row['x']->getProperty('testId'));
        }

        $this->assertEquals(1, $i);

        //Make sure there are no sean nodes
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Sean', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Check the node
        foreach ($result as $row)
        {
            $this->fail();
        }

    }

    function testRelationStartNodeUpdate(){

        $this->setExpectedException('Exception');

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $usr3 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $usr3->setFirstName('Michael');
        $usr3->setLastName('Jordon');
        $usr3->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Grab the relation
        $rel = ArachnidTest::$arachnid->reload($rel);

        //Move the start node to Michael
        $rel->setFrom($usr3);

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

    }
    function testRelationEndNodeUpdate(){

        $this->setExpectedException('Exception');

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $usr3 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $usr3->setFirstName('Michael');
        $usr3->setLastName('Jordon');
        $usr3->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Grab the relation
        $rel = ArachnidTest::$arachnid->reload($rel);

        //Move the start node to Michael
        $rel->setTo($usr3);

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

    }

    //*****************************************************
    //***** REMOVAL TESTS *********************************
    //*****************************************************
    function testNodeRemoval()
    {
        $id = $this->id;

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush and remember user
        ArachnidTest::$arachnid->persist($usr);
        ArachnidTest::$arachnid->flush();
        $usr = ArachnidTest::$arachnid->reload($usr);

        //Make sure it's there first
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Arnold', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Check the first name
        $i = 0;
        foreach ($result as $row)
        {
            $i++;
        }

        $this->assertEquals(1, $i);

        //Flush and track the time
        $t = microtime(true);
        ArachnidTest::$arachnid->remove($usr);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get node with everyman
        $queryString = "MATCH (movie:`LRezek\\Arachnid\\Tests\\Entity\\User` {firstName:'Arnold', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $result = $query->getResultSet();

        //Check the first name
        foreach ($result as $row)
        {
            //If there are any results, fail
            $this->fail();
        }
    }
    function testNodeRemovalWithRelation()
    {
        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        $usr = ArachnidTest::$arachnid->reload($usr1);

        //Make sure the node is there
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN n;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        $i = 0;

        //Check the first name
        foreach ($d as $row)
        {
            //Check the since property
            $this->assertEquals( "Arnold", $row['x']->getProperty("firstName"));

            $i++;

        }

        $this->assertEquals(1, $i);


        //Delete it
        $t = microtime(true);
        ArachnidTest::$arachnid->remove($usr);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));


        //Make sure the node was removed
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN n;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        foreach($d as $row)
        {
            $this->fail();
        }
    }
    function testRelationRemoval()
    {

        $usr1 = new Entity\User;
        $usr2 = new Entity\User;
        $rel = new Entity\FriendsWith();

        $usr1->setFirstName('Arnold');
        $usr1->setLastName('Schwarzenegger');
        $usr1->setTestId($this->id);

        $usr2->setFirstName('Sean');
        $usr2->setLastName('Connery');
        $usr2->setTestId($this->id);

        $rel->setTo($usr2);
        $rel->setFrom($usr1);
        $rel->setSince("1989");

        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();

        //Get the relation with everyman
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();

        //Check the property
        $i = 0;
        foreach ($d as $row)
        {
            //Check the since property
            $this->assertEquals($row['x']->getProperty("since"), "1989");

            $i++;

        }

        $this->assertEquals(1, $i);

        //Get relation
        $rel = ArachnidTest::$arachnid->reload($rel);

        //Remove it
        $t = microtime(true);
        ArachnidTest::$arachnid->remove($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for it again
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY(ArachnidTest::$arachnid->getClient(), $queryString);
        $d = $query->getResultSet();


        //Check the property
        foreach ($d as $row)
        {
            $this->fail();
        }

    }

    //*****************************************************
    //***** UNDERSCORE TESTS ******************************
    //*****************************************************
    function testBasicUnderscoreNotation()
    {
        $usr1 = new Entity\UserUnderscoreNotation();
        $usr2 = new Entity\UserUnderscoreNotation();
        $rel = new Entity\FriendsWithUnderscoreNotation();

        $usr1->set_first_name('user one');
        $usr1->set_last_name('one user');
        $usr1->set_test_id($this->id);

        $usr2->set_first_name('user two');
        $usr2->set_last_name('two user');
        $usr2->set_test_id($this->id);

        $rel->set_to($usr2);
        $rel->set_from($usr1);
        $rel->set_since("100");

        $t = microtime(true);
        ArachnidTest::$arachnid->persist($rel);
        ArachnidTest::$arachnid->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get the nodes
        $usr1 = ArachnidTest::$arachnid->reload($usr1);
        $usr2 = ArachnidTest::$arachnid->reload($usr2);

        //Get the relation a couple different ways
        $rel1 = ArachnidTest::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithUnderscoreNotation')->find_one_by_from($usr1);
        $rel2 = ArachnidTest::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithUnderscoreNotation')->find_one_by_to($usr2);
        $rel3 = ArachnidTest::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithUnderscoreNotation')->find_one_by_since("100");

        //Make sure there is an entry
        $this->assertNotNull($rel1);

        //Make sure all 3 match
        $this->assertEquals($rel1, $rel2);
        $this->assertEquals($rel1, $rel3);

        //Check relation
        $this->assertEquals($usr1, $rel1->get_from());
        $this->assertEquals($usr2, $rel1->get_to());
        $this->assertEquals("100", $rel1->get_since());

        //Check the start node
        $start = $rel1->get_from();
        $this->assertEquals("user one", $start->get_first_name());
        $this->assertEquals("one user", $start->get_last_name());

        //And the end node
        $end = $rel1->get_to();
        $this->assertEquals("user two", $end->get_first_name());
        $this->assertEquals("two user", $end->get_last_name());

        //Delete the relationship
        ArachnidTest::$arachnid->remove($usr1);
        ArachnidTest::$arachnid->remove($usr2);
        ArachnidTest::$arachnid->remove($rel);
        ArachnidTest::$arachnid->flush();

        //Make sure the nodes and relationship are gone
        $rel = ArachnidTest::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithUnderscoreNotation')->find_one_by_since("100");
        $usr1 = ArachnidTest::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\UserUnderscoreNotation')->find_one_by_first_name("user one");
        $usr2 = ArachnidTest::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\UserUnderscoreNotation')->find_one_by_first_name("user two");

        $this->assertNull($rel);
        $this->assertNull($usr1);
        $this->assertNull($usr2);
    }

    //*****************************************************
    //***** FUNCTIONALITY TESTS ***************************
    //*****************************************************
    function testPropertyFormats()
    {
        $user = new UserDifferentPropertyFormats();
        $user->setTestId($this->id);

        //Set a scalar
        $user->setScalar(5);

        //Set an array
        $user->setArray(array(1,2,3));

        //Set a to-Json property
        $user->setJson(array("type"=>"json", "desc"=>"this is json in the db"));

        //Set a date property
        $date = new \DateTime('now', new \DateTimeZone('UTC'));
        $user->setDate($date);

        //Set a garbage property
        $user->setGarbage(5);

        //Set an object property
        $obj = new ClassParamTestClass(1);
        $user->setClass($obj);

        //Flush
        ArachnidTest::$arachnid->persist($user);
        ArachnidTest::$arachnid->flush();
        $user = ArachnidTest::$arachnid->reload($user);

        //Do assertions
        $this->assertEquals(5, $user->getScalar());
        $this->assertEquals(array(1,2,3), $user->getArray());
        $this->assertEquals(array("type"=>"json", "desc"=>"this is json in the db"), $user->getJson());
        $this->assertEquals($date, $user->getDate());
        $this->assertEquals(null, $user->getGarbage());
        $this->assertEquals($obj, $user->getClass());

    }

    //*****************************************************
    //***** CLEARING TESTS ********************************
    //*****************************************************
    function testClearCache()
    {
        $ref = new \ReflectionClass('LRezek\\Arachnid\\Arachnid');
        $ln = $ref->getProperty('nodeProxyCache');
        $lr = $ref->getProperty('relationProxyCache');
        $n = $ref->getProperty('everymanNodeCache');
        $r = $ref->getProperty('everymanRelationCache');

        $ln->setAccessible(true);
        $lr->setAccessible(true);
        $n->setAccessible(true);
        $r->setAccessible(true);

        $ln->setValue(self::$arachnid, array(1));
        $lr->setValue(self::$arachnid, array(1));
        $n->setValue(self::$arachnid, array(1));
        $r->setValue(self::$arachnid, array(1));

        //Do the clear
        self::$arachnid->clear_cache();

        $this->assertEquals(0, count($ln->getValue(self::$arachnid)));
        $this->assertEquals(0, count($lr->getValue(self::$arachnid)));
        $this->assertEquals(0, count($n->getValue(self::$arachnid)));
        $this->assertEquals(0, count($r->getValue(self::$arachnid)));

        $ln->setAccessible(false);
        $lr->setAccessible(false);
        $n->setAccessible(false);
        $r->setAccessible(false);
    }


    //*****************************************************
    //***** STRESS TESTS **********************************
    //*****************************************************

//    function testHugeFlush()
//    {
//        $numRelations = 1000;
//
//        $t = microtime(true);
//
//        for($i = 0; $i < $numRelations; $i++)
//        {
//
//            $mov1 = new Entity\User();
//            $mov2 = new Entity\User();
//            $relation = new Entity\Sibling();
//
//            $mov1->setFirstName('Lukas');
//            $mov2->setFirstName('David');
//            $relation->setTo($mov1);
//            $relation->setFrom($mov2);
//            $relation->setSince("1993");
//
//            ArachnidTest::$arachnid->persist($relation);
//        }
//
//        ArachnidTest::$arachnid->flush();
//
//        printf($this->getMask(), __FUNCTION__, (microtime(true) - $t));
//
//        $this->clearAll();
//
//    }

}