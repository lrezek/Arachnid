<?php

namespace LRezek\Arachnid\Tests;
use Everyman\Neo4j\Cypher\Query as EM_QUERY;
use LRezek\Arachnid\Arachnid;
use LRezek\Arachnid\Tests\Entity\FriendsWith;
use LRezek\Arachnid\Tests\Entity\User;
use org\bovigo\vfs\vfsStream;

class RepositoryTest extends TestLogger
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

        //Create users
        $p1 = new User();
        $p2 = new User();
        $p3 = new User();
        $p4 = new User();
        $p5 = new User();

        //Brad -> 1990 -> Christian
        //Brad -> 1991 -> Scarlett
        //Brad -> 1992 -> Liam
        //Brad -> 1993 -> Ellen

        //Christian -> 1994 -> Brad
        //Christian -> 1995 -> Scarlett
        //Christian -> 1996 -> Liam
        //Christian -> 1997 -> Ellen

        //Scarlett -> 1998 -> Brad
        //Scarlett -> 1999 -> Christian
        //Scarlett -> 2000 -> Liam
        //Scarlett -> 2001 -> Ellen

        //Liam -> 2002 -> Brad
        //Liam -> 2003 -> Christian
        //Liam -> 2004 -> Scarlett
        //Liam -> 2005 -> Ellen

        //Ellen -> 2006 -> Brad
        //Ellen -> 2007 -> Christian
        //Ellen -> 2008 -> Scarlett
        //Ellen -> 2009 -> Liam

        //Write their properties
        $p1->setFirstName("Brad");
        $p1->setLastName("Pitt");
        $p1->setTestId($this->id);

        $p2->setFirstName("Christian");
        $p2->setLastName("Bale");
        $p2->setTestId($this->id);

        $p3->setFirstName("Scarlett");
        $p3->setLastName("Johansson");
        $p3->setTestId($this->id);

        $p4->setFirstName("Liam");
        $p4->setLastName("Neeson");
        $p4->setTestId($this->id);

        $p5->setFirstName("Ellen");
        $p5->setLastName("Page");
        $p5->setTestId($this->id);

        $nodes = array($p1, $p2, $p3, $p4, $p5);

        $year = 1990;

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
                    self::$arachnid->persist($test_rels[$i][$j]);
                }
            }
        }

        self::$arachnid->flush();

    }

    function tearDown()
    {
        $queryString = "MATCH (n {testId:'$this->id'}) OPTIONAL MATCH (n)-[r]-() DELETE n,r";
        $query = new EM_QUERY(self::$arachnid->getClient(), $queryString);
        $query->getResultSet();
    }

    //*****************************************************
    //***** BASIC TESTS ***********************************
    //*****************************************************
    function testRepositoryCreation()
    {
        //Standard camelCase
        $repo1 = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');

        //Test alternate notation
        $repo2 = self::$arachnid->get_repository('LRezek\\Arachnid\\Tests\\Entity\\User');

        $this->assertEquals($repo1, $repo2);
    }

    function testCustomRepo()
    {
        //Make the repo and grab a query the standard way
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\UserCustomRepo');
        $query = self::$arachnid->createCypherQuery();

        //Use custom repo to get queries in both notations
        $q1 = $repo->getQuery();
        $q2 = $repo->get_query();

        $this->assertEquals($query, $q1);
        $this->assertEquals($query, $q2);
    }

    function testBrokenCustomRepo()
    {
        $this->setExpectedException('Exception', 'Requested repository class LRezek\\Arachnid\\Tests\\Repo\\BrokenRepo does not extend the base repository class.');

        self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\UserBrokenRepo');
    }

    //*****************************************************
    //***** FIND ONE TESTS ********************************
    //*****************************************************
    function testNodeFindOneByProperty()
    {
        $t = microtime(true);

        //Find a node
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');

        //Do a standard findOneBy
        $user = $repo->findOneByFirstName('Brad');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Try alternate notations
        $alt = array();
        $alt[] = $repo->findOneByfirstName('Brad');
        $alt[] = $repo->findOneBy_FirstName('Brad');
        $alt[] = $repo->findOneBy_firstName('Brad');
        $alt[] = $repo->find_one_by_firstName('Brad');
        $alt[] = $repo->find_one_by_FirstName('Brad');
        $alt[] = $repo->find_one_byFirstName('Brad');
        $alt[] = $repo->find_one_byfirstName('Brad');

        //Make sure all these results are the same
        foreach($alt as $a)
        {
            $this->assertEquals($user, $a);
        }

        //Make sure the node is the right one
        $this->assertEquals("Brad", $user->getFirstName());
        $this->assertEquals("Pitt", $user->getLastName());
    }
    function testRelationFindOneByProperty()
    {
        $t = microtime(true);

        //Query for relation
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');

        //Grab the relation
        $rel = $repo->findOneBySince('1990');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Try it with all the different notations
        $alt = array();
        $alt[] = $repo->findOneBysince('1990');
        $alt[] = $repo->findOneBy_Since('1990');
        $alt[] = $repo->findOneBy_since('1990');
        $alt[] = $repo->find_one_by_since('1990');
        $alt[] = $repo->find_one_by_Since('1990');
        $alt[] = $repo->find_one_bysince('1990');
        $alt[] = $repo->find_one_bySince('1990');

        //Make sure all these results are the same
        foreach($alt as $a)
        {
            $this->assertEquals($rel, $a);
        }

        $start = $rel->getFrom();
        $end = $rel->getTo();

        //Validate the relation
        $this->assertEquals("1990", $rel->getSince());

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        $this->assertEquals("Christian", $end->getFirstName());
        $this->assertEquals("Bale", $end->getLastName());
    }

    function testNodeFindOneByCriteria()
    {
        $t = microtime(true);

        //Find said node
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');

        $user = $repo->findOneBy(array("firstName" => 'Brad'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Test alternate notation
        $user2 = $repo->find_one_by(array("firstName" => 'Brad'));
        $this->assertEquals($user, $user2);

        //Validate user
        $this->assertEquals("Brad", $user->getFirstName());
        $this->assertEquals("Pitt", $user->getLastName());

    }
    function testRelationFindOneByCriteria()
    {
        $t = microtime(true);

        //Query for relation
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');
        $rel = $repo->findOneBy(array('since' => '1991'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Test alternate notation
        $rel2= $repo->find_one_by(array('since' => '1991'));
        $this->assertEquals($rel, $rel2);

        //Validate relationship
        $start = $rel->getFrom();
        $end = $rel->getTo();

        $this->assertEquals("1991", $rel->getSince());

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        $this->assertEquals("Scarlett", $end->getFirstName());
        $this->assertEquals("Johansson", $end->getLastName());

    }

    function testRelationFindOneByStartNodeProperty()
    {

        //Find the Brad node
        $brad = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Brad');

        $t = microtime(true);

        //Find his sibling relation
        $rel = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findOneByFrom($brad);

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $start = $rel->getFrom();
        $end = $rel->getTo();

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        //The relation must be between years 1990 and 1994
        switch($rel->getSince())
        {
            //Christian Bale
            case 1990:
                $this->assertEquals("Christian", $end->getFirstName());
                $this->assertEquals("Bale", $end->getLastName());
                break;

            //Scarlet Johansson
            case 1991:
                $this->assertEquals("Scarlett", $end->getFirstName());
                $this->assertEquals("Johansson", $end->getLastName());
                break;

            //Liam Neeson
            case 1992:
                $this->assertEquals("Liam", $end->getFirstName());
                $this->assertEquals("Neeson", $end->getLastName());
                break;

            //Ellen Page
            case 1993:
                $this->assertEquals("Ellen", $end->getFirstName());
                $this->assertEquals("Page", $end->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }
    function testRelationFindOneByEndNodeProperty()
    {

        //Find the christian node
        $c = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Christian');

        $t = microtime(true);

        //Find his sibling relation
        $rel = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findOneByTo($c);

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Grab relation start and end
        $start = $rel->getFrom();
        $end = $rel->getTo();

        //Validate the end node
        $this->assertEquals("Christian", $end->getFirstName());
        $this->assertEquals("Bale", $end->getLastName());

        //The relation must be in these years
        switch($rel->getSince())
        {
            //Brad Pitt
            case 1990:
                $this->assertEquals("Brad", $start->getFirstName());
                $this->assertEquals("Pitt", $start->getLastName());
                break;

            //Christian Bale
            case 1999:
                $this->assertEquals("Christian", $start->getFirstName());
                $this->assertEquals("Bale", $start->getLastName());
                break;

            //Liam Neeson
            case 2003:
                $this->assertEquals("Liam", $start->getFirstName());
                $this->assertEquals("Neeson", $start->getLastName());
                break;

            //Ellen Page
            case 2007:
                $this->assertEquals("Ellen", $start->getFirstName());
                $this->assertEquals("Page", $start->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }

    function testNodeFindOneByWhenEmpty()
    {

        $t = microtime(true);

        //The database is empty, do the query
        $user = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Jennifer');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertNull($user);
    }
    function testRelationFindOneByWhenEmpty()
    {

        $t = microtime(true);

        //The database is empty, do the query
        $user = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findOneBySince('2050');

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertNull($user);

    }

    function testRelationFindOneByStartNodeCriteria()
    {

        //Find the brad node
        $brad = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Brad');

        $t = microtime(true);

        //Find his sibling relation
        $rel = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findOneBy(array('from' => $brad));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $start = $rel->getFrom();
        $end = $rel->getTo();

        $this->assertEquals("Brad", $start->getFirstName());
        $this->assertEquals("Pitt", $start->getLastName());

        //The relation must be between years 1990 and 1994
        switch($rel->getSince())
        {
            //Christian Bale
            case 1990:
                $this->assertEquals("Christian", $end->getFirstName());
                $this->assertEquals("Bale", $end->getLastName());
                break;

            //Scarlet Johansson
            case 1991:
                $this->assertEquals("Scarlett", $end->getFirstName());
                $this->assertEquals("Johansson", $end->getLastName());
                break;

            //Liam Neeson
            case 1992:
                $this->assertEquals("Liam", $end->getFirstName());
                $this->assertEquals("Neeson", $end->getLastName());
                break;

            //Ellen Page
            case 1993:
                $this->assertEquals("Ellen", $end->getFirstName());
                $this->assertEquals("Page", $end->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }
    function testRelationFindOneByEndNodeCriteria()
    {

        //Find the christian node
        $c = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findOneByFirstName('Christian');

        $t = microtime(true);

        //Find his sibling relation
        $rel = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findOneBy(array('to' => $c));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Grab relation start and end
        $start = $rel->getFrom();
        $end = $rel->getTo();

        //Validate the end node
        $this->assertEquals("Christian", $end->getFirstName());
        $this->assertEquals("Bale", $end->getLastName());

        //The relation must be in these years
        switch($rel->getSince())
        {
            //Brad Pitt
            case 1990:
                $this->assertEquals("Brad", $start->getFirstName());
                $this->assertEquals("Pitt", $start->getLastName());
                break;

            //Christian Bale
            case 1999:
                $this->assertEquals("Christian", $start->getFirstName());
                $this->assertEquals("Bale", $start->getLastName());
                break;

            //Liam Neeson
            case 2003:
                $this->assertEquals("Liam", $start->getFirstName());
                $this->assertEquals("Neeson", $start->getLastName());
                break;

            //Ellen Page
            case 2007:
                $this->assertEquals("Ellen", $start->getFirstName());
                $this->assertEquals("Page", $start->getLastName());
                break;

            default:
                $this->fail();
                break;
        }

    }

    function testNodeFindOneByNonExisting()
    {
        $t = microtime(true);

        //Find said node
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');

        $user = $repo->findOneBy(array("firstName" => 'non-existing'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Validate user
        $this->assertNull($user);
    }
    function testRelationFindOneByNonExisting()
    {
        $t = microtime(true);

        //Find said relation
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');

        $rel = $repo->findOneBy(array("since" => 'non-existing'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Validate user
        $this->assertNull($rel);
    }

    function testFindOneByNothing()
    {
        $this->setExpectedException('Exception', "Please supply at least one criteria to findOneBy().");

        //Try to find a node without any criteria
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');
        $user = $repo->findOneBy(array());
    }

    //*****************************************************
    //***** FIND BY TESTS *********************************
    //*****************************************************
    function testNodeFindByProperty()
    {

        //Make 3 nodes
        for($i = 0; $i < 3; $i++)
        {
            $mov2 = new Entity\User;
            $mov2->setFirstName('Bradley');
            $mov2->setLastName("Cooper");
            $mov2->setTestId($this->id);
            self::$arachnid->persist($mov2);
        }

        self::$arachnid->flush();

        $t = microtime(true);

        //Find the 3 nodes
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');
        $nodes = $repo->findByFirstName("Bradley")->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Test alternate notations
        $alt = array();
        $alt[] = $repo->findByfirstName('Bradley')->toArray();
        $alt[] = $repo->findBy_FirstName('Bradley')->toArray();
        $alt[] = $repo->findBy_firstName('Bradley')->toArray();
        $alt[] = $repo->find_by_firstName('Bradley')->toArray();
        $alt[] = $repo->find_by_FirstName('Bradley')->toArray();
        $alt[] = $repo->find_byFirstName('Bradley')->toArray();
        $alt[] = $repo->find_byfirstName('Bradley')->toArray();

        //Make sure all these results are the same
        foreach($alt as $a)
        {
            $this->assertEquals($nodes, $a);
        }

        //Make sure there are 3 nodes
        $this->assertEquals(count($nodes), 3);

        //Make sure they contain the proper values
        foreach($nodes as $node)
        {
            $this->assertEquals($node->getFirstName(), "Bradley");
            $this->assertEquals($node->getLastName(), "Cooper");
        }

    }
    function testRelationFindByProperty()
    {

        //Make 3 relations
        for($i = 0; $i < 3; $i++)
        {
            $mov1 = new Entity\User;
            $mov1->setFirstName('Dane');
            $mov1->setLastName('Cook');
            $mov1->setTestId($this->id);

            $mov2 = new Entity\User;
            $mov2->setFirstName('Chris');
            $mov2->setLastName('Tucker');
            $mov2->setTestId($this->id);

            $relation = new Entity\FriendsWith();
            $relation->setTo($mov1);
            $relation->setFrom($mov2);
            $relation->setSince("2050");

            self::$arachnid->persist($relation);
        }

        self::$arachnid->flush();

        $t = microtime(true);
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');
        $relations = $repo->findBySince("2050")->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Test alternate notations
        $alt = array();
        $alt[] = $repo->findBysince('2050')->toArray();
        $alt[] = $repo->findBy_Since('2050')->toArray();
        $alt[] = $repo->findBy_since('2050')->toArray();
        $alt[] = $repo->find_by_since('2050')->toArray();
        $alt[] = $repo->find_by_Since('2050')->toArray();
        $alt[] = $repo->find_bySince('2050')->toArray();
        $alt[] = $repo->find_bysince('2050')->toArray();

        //Make sure all these results are the same
        foreach($alt as $a)
        {
            $this->assertEquals($relations, $a);
        }

        //Make sure there are 3 nodes
        $this->assertEquals(count($relations), 3);

        //Make sure they contain the proper values
        foreach($relations as $rel)
        {
            $this->assertEquals($rel->getSince(), "2050");
        }

    }

    function testNodeFindByCriteria()
    {

        //Make 3 nodes
        for($i = 0; $i < 3; $i++)
        {
            $mov2 = new Entity\User;
            $mov2->setFirstName('Uma');
            $mov2->setLastName("Therman");
            $mov2->setTestId($this->id);
            self::$arachnid->persist($mov2);
        }

        self::$arachnid->flush();

        $t = microtime(true);

        //Find the 3 nodes
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');
        $nodes = $repo->findBy(array('firstName' => 'Uma', 'lastName' => 'Therman'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Test alternate notation
        $nodes2 = $repo->find_by(array('firstName' => 'Uma', 'lastName' => 'Therman'))->toArray();
        $this->assertEquals($nodes, $nodes2);

        //Make sure there are 3 nodes
        $this->assertEquals(count($nodes), 3);

        //Make sure they contain the proper values
        foreach($nodes as $node)
        {
            $this->assertEquals($node->getFirstName(), "Uma");
            $this->assertEquals($node->getLastName(), "Therman");
        }

    }
    function testRelationFindByCriteria()
    {

        //Make 3 relations
        for($i = 0; $i < 3; $i++)
        {
            $mov1 = new Entity\User;
            $mov1->setFirstName('Will');
            $mov1->setLastName('Smith');
            $mov1->setTestId($this->id);

            $mov2 = new Entity\User;
            $mov2->setFirstName('Michael');
            $mov2->setLastName('Cera');
            $mov2->setTestId($this->id);

            $relation = new Entity\FriendsWith();
            $relation->setTo($mov1);
            $relation->setFrom($mov2);
            $relation->setSince("2051");

            self::$arachnid->persist($relation);
        }


        self::$arachnid->flush();

        $t = microtime(true);

        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');
        $relations = $repo->findBy(array('since' => '2051'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Test alternate notation
        $rel2 = $repo->find_by(array('since' => '2051'))->toArray();
        $this->assertEquals($relations, $rel2);

        //Make sure there are 3 relations
        $this->assertEquals(count($relations), 3);

        //Make sure they contain the proper values
        foreach($relations as $rel)
        {
            $this->assertEquals($rel->getSince(), "2051");
        }

    }

    function testNodeFindByWhenEmpty()
    {
        $t = microtime(true);

        //Do the query
        $users = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User')->findBy(array('firstName' => 'Martha'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertEquals(count($users), 0);
    }
    function testRelationFindByWhenEmpty()
    {
        $t = microtime(true);

        //Do the query
        $users = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findBy(array('since' => '3000'))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Make sure there's nothing there
        $this->assertEquals(count($users), 0);

    }

    function testRelationFindByStartNodeProperty()
    {

        $mov1 = new Entity\User;
        $mov1->setFirstName('Charlie');
        $mov1->setLastName('Sheen');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('Jamie');
        $mov2->setLastName('Foxx');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2052");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2053");

        self::$arachnid->persist($rel1);
        self::$arachnid->persist($rel2);
        self::$arachnid->flush();

        //Grab the Jamie node
        $jam = self::$arachnid->reload($mov2);

        $t = microtime(true);
        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findByFrom($jam)->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }
    function testRelationFindByEndNodeProperty()
    {

        $mov1 = new Entity\User;
        $mov1->setFirstName('Jerry');
        $mov1->setLastName('Seinfeld');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('Clint');
        $mov2->setLastName('Eastwood');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2054");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2055");

        self::$arachnid->persist($rel1);
        self::$arachnid->persist($rel2);
        self::$arachnid->flush();

        //Grab jerry
        $jerry = self::$arachnid->reload($mov1);

        $t = microtime(true);
        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findByTo($jerry)->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }

    function testRelationFindByStartNodeCriteria()
    {
        $mov1 = new Entity\User;
        $mov1->setFirstName('Alec');
        $mov1->setLastName('Baldwin');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('John');
        $mov2->setLastName('Travolta');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2056");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2057");

        self::$arachnid->persist($rel1);
        self::$arachnid->persist($rel2);
        self::$arachnid->flush();

        //Grab the john node
        $john = self::$arachnid->reload($mov2);

        $t = microtime(true);

        //Find his sibling relations
        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findBy(array('from' => $john))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }
    function testRelationFindByEndNodeCriteria()
    {
        $mov1 = new Entity\User;
        $mov1->setFirstName('Simon');
        $mov1->setLastName('Cowell');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User;
        $mov2->setFirstName('Tiger');
        $mov2->setLastName('Woods');
        $mov2->setTestId($this->id);

        $rel1 = new Entity\FriendsWith();
        $rel1->setTo($mov1);
        $rel1->setFrom($mov2);
        $rel1->setSince("2058");

        $rel2 = new Entity\FriendsWith();
        $rel2->setTo($mov1);
        $rel2->setFrom($mov2);
        $rel2->setSince("2059");

        self::$arachnid->persist($rel1);
        self::$arachnid->persist($rel2);
        self::$arachnid->flush();

        //Grab simon
        $simon = self::$arachnid->reload($mov1);

        $t = microtime(true);

        //Find his sibling relation
        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findBy(array('to' => $simon))->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }

    function testNodeFindByNonExisting()
    {
        $t = microtime(true);

        //Find said node
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');

        $users = $repo->findBy(array("firstName" => 'non-existing'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Validate user
        $this->assertEmpty(count($users));
    }
    function testRelationFindByNonExisting()
    {
        $t = microtime(true);

        //Find said relation
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');

        $rels = $repo->findBy(array("since" => 'non-existing'));

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        //Validate user
        $this->assertEmpty($rels);
    }

    function testFindByNothing()
    {
        $this->setExpectedException('Exception', "Please supply at least one criteria to findBy().");

        //Try to find a node without any criteria
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');
        $user = $repo->findBy(array());
    }

    //*****************************************************
    //***** FIND ALL TESTS ********************************
    //*****************************************************
    function testNodeFindAll()
    {
        //Create nodes
        $mov1 = new Entity\Person();
        $mov1->setFirstName('Orlando');
        $mov1->setLastName('Bloom');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\Person();
        $mov2->setFirstName('Mila');
        $mov2->setLastName('Kunis');
        $mov2->setTestId($this->id);

        self::$arachnid->persist($mov1);
        self::$arachnid->persist($mov2);
        self::$arachnid->flush();

        $t = microtime(true);
        $nodes = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\Person')->findAll()->toArray();
        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($nodes), 2);
    }
    function testRelationFindAll()
    {

        $mov1 = new Entity\User();
        $mov1->setFirstName('Owen');
        $mov1->setLastName('Wilson');
        $mov1->setTestId($this->id);

        $mov2 = new Entity\User();
        $mov2->setFirstName('Morgan');
        $mov2->setLastName('Freeman');
        $mov2->setTestId($this->id);

        $mov3 = new Entity\User();
        $mov3->setFirstName('Katy');
        $mov3->setLastName('Perry');
        $mov3->setTestId($this->id);

        //Create relation from David to Lukas
        $relation = new Entity\Likes();
        $relation->setTo($mov1);
        $relation->setFrom($mov2);
        $relation->setSince("2060");

        //Create relation from David to Nicole
        $relation2 = new Entity\Likes();
        $relation2->setTo($mov3);
        $relation2->setFrom($mov2);
        $relation2->setSince("2061");

        self::$arachnid->persist($relation);
        self::$arachnid->persist($relation2);
        self::$arachnid->flush();

        $t = microtime(true);

        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\Likes')->find_all()->toArray();

        $this->printTime(__FUNCTION__, (microtime(true) - $t));

        $this->assertEquals(count($rels), 2);

    }

    //*****************************************************
    //***** MISC TESTS ************************************
    //*****************************************************
    function testNodeFindByGarbageProperty()
    {
        $this->setExpectedException('Exception', "Property noName is not indexed or does not exist in LRezek\\Arachnid\\Tests\\Entity\\User.");

        //Find a node without a real property
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\User');
        $repo->findByNoName("x");
    }
    function testRelationFindByGarbageProperty()
    {
        $this->setExpectedException('Exception', "Property noName is either not indexed, not a start/end, or does not exist in LRezek\\Arachnid\\Tests\\Entity\\FriendsWith.");

        //Find a node without a real property
        $repo = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');
        $repo->findByNoName("x");
    }
    function testRelationFindByInvalidNode()
    {
        $this->setExpectedException('InvalidArgumentException', "You must supply a node to search for relations by node.");
        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findByFrom(new Entity\FriendsWith());
    }
    function testRelationFindByUnsavedNode()
    {
        $this->setExpectedException('InvalidArgumentException', "Node must be saved to find its relations.");
        $rels = self::$arachnid->getRepository('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith')->findByFrom(new Entity\User());
    }

}