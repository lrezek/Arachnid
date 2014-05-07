<?php

namespace LRezek\Neo4j\Tests\Entity;
use LRezek\Neo4j\Annotation as OGM;

/**
 * @OGM\Node
 */
class User
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $firstName;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $lastName;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $testId;

    /**
     * @OGM\Property
     */
    protected $prop1;

    function getId()
    {
        return $this->id;
    }

    function setId($id)
    {
        $this->id = $id;
    }

    function getFirstName()
    {
        return $this->firstName;
    }

    function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    function getLastName()
    {
        return $this->lastName;
    }

    function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    function setTestId($id)
    {
        $this->testId = $id;
    }

    function getTestId()
    {
        return $this->testId;
    }


}

