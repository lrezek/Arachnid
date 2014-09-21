<?php

namespace LRezek\Arachnid\Tests\Entity\Broken;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Node
 */
class UserIndexOnly
{
    /**
     * @OGM\Auto
     */
    protected $id;

    /**
     * @OGM\Index
     */
    protected $name;
}

