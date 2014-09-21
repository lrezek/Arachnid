<?php
namespace LRezek\Arachnid\Tests\Entity\Broken;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Relation
 */
class FriendsWithIndexedEnd
{
	/**
	 * @OGM\Auto
	 */
	protected $id;

    /**
     * @OGM\Start
     */
    protected $from;

    /**
     * @OGM\End
     * @OGM\Index
     */
    protected $to;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $since;

}

