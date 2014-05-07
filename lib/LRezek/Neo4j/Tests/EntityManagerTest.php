<?php

namespace LRezek\Neo4j\Tests;
use Everyman\Neo4j\Relationship;
use LRezek\Neo4j\EntityManager;
use LRezek\Neo4j\Meta\Property;
use Everyman\Neo4j\Cypher\Query as EM_QUERY;

class EntityManagerTest extends DatabaseTestCase
{
    //TODO: Query tests

    function __construct()
    {
        //Generate a ID, so nodes can easily be found and deleted after tests
        $this->id = uniqid();

    }
    function __destruct()
    {
        $id = $this->id;
        $em = $this->getEntityManager();

        $queryString = "MATCH (n {testId:'$id'}) OPTIONAL MATCH (n)-[r]-() DELETE n,r";
        $query = new EM_QUERY($em->getClient(), $queryString);
        $result = $query->getResultSet();
    }

    //*****************************************************
    //***** FLUSH TESTS ***********************************
    //*****************************************************
    function testNodeFlush()
    {
        $em = $this->getEntityManager();

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush and track the time
        $t = microtime(true);
        $em->persist($usr);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get node with everyman
        $queryString = 'MATCH (movie:`LRezek\Neo4j\Tests\Entity\User` {firstName:"Arnold"}) RETURN movie;';
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();

        $t = microtime(true);
        $em->persist($rel);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get the relation with everyman
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();

        $t = microtime(true);
        $em->persist($relation);
        $em->persist($relation2);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get the first relation with everyman
        $queryString = "MATCH (n)-[r {since:'1989'}]->(m) RETURN r;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $em = $this->getEntityManager();
        $em->persist($usr);
        $em->flush();
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

        $em = $this->getEntityManager();

        $t = microtime(true);
        $em->persist($rel);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

    }

    function testRelationFlushWithoutStart() {

        $this->setExpectedException('Exception');

        $em = $this->getEntityManager();

        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $relation = new Entity\FriendsWith();
        $relation->setTo($usr);
        $relation->setSince("1989");

        $t = microtime(true);
        $em->persist($relation);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));
    }
    function testRelationFlushWithoutEnd() {

        $this->setExpectedException('Exception');

        $em = $this->getEntityManager();

        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $relation = new Entity\FriendsWith();
        $relation->setFrom($usr);
        $relation->setSince("1989");

        $em->persist($relation);
        $em->flush();
    }

    //*****************************************************
    //***** RELOAD TESTS **********************************
    //*****************************************************
    function testNodeReload()
    {
        $em = $this->getEntityManager();

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush it
        $em->persist($usr);
        $em->flush();

        //Reload
        $usr = $em->reload($usr);

        foreach(class_implements(get_class($usr)) as $key => $val)
        {
            if($val != 'LRezek\\Neo4j\\Proxy\\Entity')
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
        $em = $this->getEntityManager();

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
        $em->persist($rel);
        $em->flush();

        //Reload
        $rel = $em->reload($rel);
        $usr1 = $em->reload($usr1);
        $usr2 = $em->reload($usr2);

        foreach(class_implements(get_class($rel)) as $key => $val)
        {
            if($val != 'LRezek\\Neo4j\\Proxy\\Entity')
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

        $em = $this->getEntityManager();

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        $usr = $em->reload($usr);
    }
    function testReloadUnsavedRelation()
    {
        $this->setExpectedException('Exception');

        $em = $this->getEntityManager();

        $em = $this->getEntityManager();

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

        $rel = $em->reload($rel);
    }

    //*****************************************************
    //***** CONSTRUCTION TESTS ****************************
    //*****************************************************
    function testNullConfiguration(){

        $em = new EntityManager(null);
        $em->clear();
    }
    function testGarbageConfiguration() {

        $this->setExpectedException('Exception');
        $em = new EntityManager(7);

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
        $em = $this->getEntityManager();
        $em->persist($usr);
        $em->flush();

        //Reload the node
        $usr = $em->reload($usr);

        //Change the name
        $usr->setFirstName('Arnie');

        $t = microtime(true);
        $em->persist($usr);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for the node with everyman
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Arnie', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        //Change the since
        $rel = $em->reload($rel);
        $rel->setSince('1988');

        $t = microtime(true);
        $em->persist($rel);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for relation with everyman
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1988'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        //Grab the relation and its start
        $rel = $em->reload($rel);
        $start = $rel->getFrom();

        //Make sure it's the right node
        $this->assertEquals('Arnold', $start->getFirstName());

        //Change the name
        $start->setFirstName('Arnie');

        $t = microtime(true);
        $em->persist($rel);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for the node with everyman
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Arnie', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Arnold', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        //Grab the relation and its start
        $rel = $em->reload($rel);
        $end = $rel->getTo();

        //Make sure it's the right node
        $this->assertEquals('Sean', $end->getFirstName());

        //Change the name
        $end->setFirstName('Thomas Sean');

        $t = microtime(true);
        $em->persist($rel);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for the node with everyman
        $id = $this->id;
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Thomas Sean', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Sean', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        //Grab the relation
        $rel = $em->reload($rel);

        //Move the start node to Michael
        $rel->setFrom($usr3);

        $em->persist($rel);
        $em->flush();

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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        //Grab the relation
        $rel = $em->reload($rel);

        //Move the start node to Michael
        $rel->setTo($usr3);

        $em->persist($rel);
        $em->flush();

    }


    //*****************************************************
    //***** REMOVAL TESTS *********************************
    //*****************************************************
    function testNodeRemoval()
    {
        $em = $this->getEntityManager();
        $id = $this->id;

        //Make node with API
        $usr = new Entity\User;
        $usr->setFirstName('Arnold');
        $usr->setLastName('Schwarzenegger');
        $usr->setTestId($this->id);

        //Flush and remember user
        $em->persist($usr);
        $em->flush();
        $usr = $em->reload($usr);

        //Make sure it's there first
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Arnold', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $em->remove($usr);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Get node with everyman
        $queryString = "MATCH (movie:`LRezek\\Neo4j\\Tests\\Entity\\User` {firstName:'Arnold', testId:'$id'}) RETURN movie;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        $usr = $em->reload($usr1);

        //Make sure the node is there
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN n;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $em->remove($usr);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));


        //Make sure the node was removed
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN n;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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

        $em = $this->getEntityManager();
        $em->persist($rel);
        $em->flush();

        //Get the relation with everyman
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY($em->getClient(), $queryString);
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
        $rel = $em->reload($rel);

        //Remove it
        $t = microtime(true);
        $em->remove($rel);
        $em->flush();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Query for it again
        $id = $this->id;
        $queryString = "MATCH (n {firstName:'Arnold', testId:'$id'})-[r {since:'1989'}]->(m {firstName:'Sean'}) RETURN r;";
        $query = new EM_QUERY($em->getClient(), $queryString);
        $d = $query->getResultSet();


        //Check the property
        foreach ($d as $row)
        {
            $this->fail();
        }

    }

    //*****************************************************
    //***** STRESS TESTS **********************************
    //*****************************************************

//    function testHugeFlush()
//    {
//        $numRelations = 1000;
//
//        $em = $this->getEntityManager();
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
//            $em->persist($relation);
//        }
//
//        $em->flush();
//
//        printf($this->getMask(), __FUNCTION__, (microtime(true) - $t));
//
//        $this->clearAll();
//
//    }

}