<?php

namespace LRezek\Arachnid\Tests;
use LRezek\Arachnid\Meta\Repository as MetaRepository;
use LRezek\Arachnid\Tests\Entity\ClassParamTestClass;

class MetaTest extends \PHPUnit_Framework_TestCase
{
    //*****************************************************
    //***** PROPERTY RETRIEVAL TESTS **********************
    //*****************************************************
    function testGetIndexedProperties()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\User');

        $names = array();
        foreach ($meta->getIndexedProperties() as $property) {
            $names[] = $property->getName();
        }

        $this->assertEquals(array('firstName', 'lastName', 'testId'), $names);
    }
    function testGetProperties()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\User');

        $names = array();
        foreach ($meta->getProperties() as $property) {
            $names[] = $property->getName();
        }

        $this->assertEquals(array('firstName', 'lastName', 'testId', 'prop1'), $names);
    }
    function testGetStart()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');

        $start = $meta->getStart();

        $this->assertEquals('from', $start->getName());
    }
    function testGetEnd()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWith');

        $start = $meta->getEnd();

        $this->assertEquals('to', $start->getName());
    }
    function testNonProperty()
    {
        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserAnnotationlessProperty');

        //Make sure it's not in properties
        $names = array();
        foreach ($meta->getProperties() as $property) {
            $names[] = $property->getName();
        }

        if(in_array("prop1", $names))
        {
            $this->fail();
        }

        //Make sure it's not in indexed properties
        $names = array();
        foreach ($meta->getIndexedProperties() as $property) {
            $names[] = $property->getName();
        }

        if(in_array("prop1", $names))
        {
            $this->fail();
        }
    }

    //*****************************************************
    //***** ANNOTATION EXCEPTION TESTS ********************
    //*****************************************************
    function testAnnotationlessEntity() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\AnnotationlessEntity is not declared as a node or relation.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\AnnotationlessEntity');
    }

    function testNodeRelationEntity() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\NodeRelationEntity is defined as both a node and relation.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\NodeRelationEntity');

    }

    function testNodeMultipleAuto() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\UserMultipleAuto contains multiple targets for @Auto.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\UserMultipleAuto');

    }
    function testNodeNoAuto() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\UserNoAuto contains no @Auto property.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\UserNoAuto');
    }

    function testRelationMultipleAuto() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\FriendsWithMultipleAuto contains multiple targets for @Auto.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithMultipleAuto');

    }
    function testRelationNoAuto() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\FriendsWithNoAuto contains no @Auto property.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithNoAuto');
    }

    function testRelationMultipleStart() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\FriendsWithMultipleStart contains multiple targets for @Start.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithMultipleStart');

    }
    function testRelationNoStart() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\FriendsWithNoStart contains no targets for @Start.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithNoStart');

    }

    function testRelationMultipleEnd() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\FriendsWithMultipleEnd contains multiple targets for @End.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithMultipleEnd');

    }
    function testNoEndRelation() {

        $this->setExpectedException('Exception', 'Class LRezek\Arachnid\Tests\Entity\Broken\FriendsWithNoEnd contains no targets for @End.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithNoEnd');

    }
    function testIndexedAuto() {

        $this->setExpectedException('Exception', 'Invalid annotation combination on id in LRezek\Arachnid\Tests\Entity\Broken\IndexedAuto.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\IndexedAuto');

    }
    function testIndexOnly() {

        $this->setExpectedException('Exception', '@Index cannot be the only annotation on name in LRezek\Arachnid\Tests\Entity\Broken\UserIndexOnly.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\UserIndexOnly');

    }
    function testIndexedEnd() {

        $this->setExpectedException('Exception', 'Invalid annotation combination on to in LRezek\Arachnid\Tests\Entity\Broken\FriendsWithIndexedEnd.');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\FriendsWithIndexedEnd');

    }
    function testNodeWithStart() {

        $this->setExpectedException('Exception', 'A node entity cannot contain a start property (@Start).');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\NodeWithStart');

    }
    function testNodeWithEnd() {

        $this->setExpectedException('Exception', 'A node entity cannot contain an end property (@End).');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\Broken\\NodeWithEnd');

    }

    //*****************************************************
    //***** PROPERTY TESTS ********************************
    //*****************************************************
    function testScalarProperty() {

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserDifferentPropertyFormats');

        $usr = new Entity\UserDifferentPropertyFormats;

        $usr->setScalar(3);

        foreach($meta->getProperties() as $prop)
        {
            if($prop->getFormat() == "scalar" && !$prop->isIndexed()) //Skip the indexed test id
            {
                //Test the get
                if($prop->getValue($usr) != 3)
                {
                    $this->fail();
                }

                //Test the set
                $prop->setValue($usr, 5);
                if($usr->getScalar() != 5)
                {
                    $this->fail();
                }

                return;
            }
        }

        $this->fail();

    }
    function testArrayProperty() {

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserDifferentPropertyFormats');

        $usr = new Entity\UserDifferentPropertyFormats;

        $array = array(3,5);

        $usr->setArray($array);

        foreach($meta->getProperties() as $prop)
        {
            if($prop->getFormat() == "array")
            {
                //Test the get
                if($prop->getValue($usr) != serialize($array))
                {
                    $this->fail();
                }

                //Test the set
                $prop->setValue($usr, serialize(array(5,3)));
                if($usr->getArray() != array(5,3))
                {
                    $this->fail();
                }

                return;
            }
        }

        $this->fail();

    }
    function testJsonProperty() {

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserDifferentPropertyFormats');

        $usr = new Entity\UserDifferentPropertyFormats;

        $json = "JSON_STRING";

        $usr->setJson($json);

        foreach($meta->getProperties() as $prop)
        {
            if($prop->getFormat() == "json")
            {
                //Test the get
                if($prop->getValue($usr) != json_encode($json))
                {
                    $this->fail();
                }

                //Test the set
                $prop->setValue($usr, json_encode("JSON_STRING THE SECOND"));
                if($usr->getJson() != "JSON_STRING THE SECOND")
                {
                    $this->fail();
                }

                return;
            }
        }

        $this->fail();

    }
    function testDateProperty() {

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserDifferentPropertyFormats');

        $usr = new Entity\UserDifferentPropertyFormats;

        $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $usr->setDate($currentDate);

        foreach($meta->getProperties() as $prop)
        {
            if($prop->getFormat() == "date")
            {
                //Test the get with something
                if($prop->getValue($usr) != $currentDate->format('Y-m-d H:i:s'))
                {
                    $this->fail();
                }

                //Test the set with something
                $d2 = new \DateTime('now', new \DateTimeZone('UTC'));
                $prop->setValue($usr, $d2->format('Y-m-d H:i:s'));
                if($usr->getDate() != $d2)
                {
                    $this->fail();
                }

                //Test the get with null
                $usr->setDate(null);
                if($prop->getValue($usr) != null)
                {
                    $this->fail();
                }

                //Test the set with null
                $usr->setDate($currentDate);
                $prop->setValue($usr, null);
                if($usr->getDate() != null)
                {
                    $this->fail();
                }

                return;
            }
        }

        $this->fail();

    }
    function testGarbageProperty() {

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserDifferentPropertyFormats');

        $usr = new Entity\UserDifferentPropertyFormats;

        $usr->setGarbage(3);

        foreach($meta->getProperties() as $prop)
        {
            if($prop->getFormat() == "garbage")
            {
                //Test the get
                if($prop->getValue($usr) != null)
                {
                    $this->fail();
                }

                //Test the set
                $prop->setValue($usr, 5);
                if($usr->getGarbage() != 3)
                {
                    $this->fail();
                }

                return;
            }
        }

        $this->fail();

    }
    function testClassProperty() {

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserDifferentPropertyFormats');

        $usr = new Entity\UserDifferentPropertyFormats;

        $testClass = new ClassParamTestClass(1);

        $usr->setClass($testClass);
        $encoded = serialize($testClass);

        foreach($meta->getProperties() as $prop)
        {
            if($prop->getFormat() == "class")
            {
                //Test the get
                if($prop->getValue($usr) != $encoded)
                {
                    $this->fail();
                }

                //Test the set
                $tc = serialize(new ClassParamTestClass(2));
                $prop->setValue($usr, $tc);
                if($usr->getClass() != unserialize($tc))
                {
                    $this->fail();
                }

                return;
            }
        }

        $this->fail();
    }

}

