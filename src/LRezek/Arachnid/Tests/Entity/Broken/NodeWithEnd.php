<?php

namespace LRezek\Arachnid\Tests\Entity\Broken;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Node
 */
class NodeWithEnd
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\End
     */
    protected $name;
    
}

