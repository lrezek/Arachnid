<?php

namespace LRezek\Arachnid\Tests\Entity\Broken;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Node
 */
class NodeWithStart
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Start
     */
    protected $name;

}

