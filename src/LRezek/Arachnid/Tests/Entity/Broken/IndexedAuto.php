<?php

namespace LRezek\Arachnid\Tests\Entity\Broken;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Node
 */
class IndexedAuto
{
    /**
     * @OGM\Auto
     * @OGM\Index
     */
    protected $id;
}

