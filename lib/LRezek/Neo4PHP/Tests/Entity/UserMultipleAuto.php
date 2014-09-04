<?php

namespace LRezek\Neo4PHP\Tests\Entity;
use LRezek\Neo4PHP\Annotation as OGM;

/**
 * @OGM\Node
 */
class UserMultipleAuto
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Auto
     */
    protected $name;

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

