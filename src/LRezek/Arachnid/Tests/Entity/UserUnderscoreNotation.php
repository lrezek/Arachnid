<?php

namespace LRezek\Arachnid\Tests\Entity;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Node
 */
class UserUnderscoreNotation
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $first_name;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $last_name;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $test_id;

    /**
     * @OGM\Property
     */
    protected $prop1;

    function get_id()
    {
        return $this->id;
    }

    function set_id($id)
    {
        $this->id = $id;
    }

    function get_first_name()
    {
        return $this->first_name;
    }

    function set_first_name($firstName)
    {
        $this->first_name = $firstName;
    }

    function get_last_name()
    {
        return $this->last_name;
    }

    function set_last_name($lastName)
    {
        $this->last_name = $lastName;
    }

    function set_test_id($id)
    {
        $this->test_id = $id;
    }

    function get_test_id()
    {
        return $this->test_id;
    }


}

