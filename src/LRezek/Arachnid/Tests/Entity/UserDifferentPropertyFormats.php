<?php

namespace LRezek\Arachnid\Tests\Entity;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Node
 */
class UserDifferentPropertyFormats
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $testId;

    /**
     * @OGM\Property
     */
    protected $scalar;

    /**
     * @OGM\Property(format="array")
     */
    protected $array;

    /**
     * @OGM\Property(format="json")
     */
    protected $json;

    /**
     * @OGM\Property(format="date")
     */
    protected $date;

    /**
     * @OGM\Property(format="garbage")
     */
    protected $garbage;

    /**
     * @OGM\Property(format="class")
     */
    protected $class;

    function setArray($array) {
        $this->array = $array;
    }

    function setJson($array) {
        $this->json = $array;
    }

    function setDate($array) {
        $this->date = $array;
    }

    function setGarbage($stuff) {
        $this->garbage = $stuff;
    }

    function setScalar($stuff) {
        $this->scalar = $stuff;
    }

    function getArray() {
        return $this->array;
    }

    function getJson() {
        return $this->json;
    }

    function getDate() {
        return $this->date;
    }

    function getGarbage() {
        return $this->garbage;
    }

    function getScalar() {
        return $this->scalar;
    }

    function setClass($stuff)
    {
        $this->class = $stuff;
    }

    function getClass()
    {
        return $this->class;
    }

    function getTestId()
    {
        return $this->testId;
    }

    function setTestId($id)
    {
        $this->testId = $id;
    }
}
