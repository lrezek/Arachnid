<?php
namespace LRezek\Arachnid\Tests\Entity;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Relation
 */
class FriendsWithNoEnd
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
     * @OGM\Property
     * @OGM\Index
     */
    protected $since;


	function getFrom()
	{
		return $this->from;
	}

	function setFrom(User $from)
	{
		$this->from = $from;
	}

	function getSince()
	{
		return $this->since;
	}

	function setSince($since)
	{
		$this->since = $since;
	}

    function getId()
    {
        return $this->id;
    }

    function setId($id)
    {
        $this->id = $id;
    }
}

