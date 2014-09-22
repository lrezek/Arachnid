<?php

namespace LRezek\Arachnid\Tests\Entity;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Relation
 */
class RelationDifferentMethodTypes
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
     * @OGM\Start
     */
    protected $from;

    /**
     * @OGM\End
     */
    protected $to;

    /**
     * @OGM\Property
     */
    protected $extra;

    //Optional setFrom
    function setFrom($node, $extra = 1)
    {
        $this->from = $node;
        $this->extra = $extra;
    }

    //Pass by ref test
    function setTo(& $node)
    {
        $node->setFirstName('Lukas');

        $this->to = $node;
    }

    //Typed set from
    function set_from(User $node)
    {
        $this->from = $node;
    }

    //Pass by ref test
    function set_to(array $stuff)
    {
        $this->to = $stuff[0];
        $this->extra = $stuff[1];
    }

    function setTestId($id)
    {
        $this->testId = $id;
    }

    function getTestId()
    {
        return $this->testId;
    }

    function getExtra()
    {
        return $this->extra;
    }
}

