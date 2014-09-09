<?php

namespace LRezek\Arachnid\Tests;
use LRezek\Arachnid\Meta\Repository as MetaRepository;

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

    //*****************************************************
    //***** ANNOTATION EXCEPTION TESTS ********************
    //*****************************************************
    function testAnnotationlessEntity() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\AnnotationlessEntity');
    }

    function testNodeMultipleAuto() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserMultipleAuto');

    }
    function testNodeNoAuto() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\UserNoAuto');
    }

    function testRelationMultipleAuto() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithMultipleAuto');

    }
    function testRelationNoAuto() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithNoAuto');
    }

    function testRelationMultipleStart() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithMultipleStart');

    }
    function testRelationNoStart() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithNoStart');

    }

    function testRelationMultipleEnd() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithMultipleEnd');

    }
    function testNoEndRelation() {

        $this->setExpectedException('Exception');

        $repo = new MetaRepository;
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\FriendsWithNoEnd');

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
            if($prop->getFormat() == "scalar")
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

}

