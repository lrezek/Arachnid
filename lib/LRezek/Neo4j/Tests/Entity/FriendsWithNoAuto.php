<?php
namespace LRezek\Neo4j\Tests\Entity;
use LRezek\Neo4j\Annotation as OGM;

/**
 * @OGM\Relation
 */
class FriendsWithNoAuto
{
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

	function getTo()
	{
		return $this->to;
	}

	function setTo(User $to)
	{
		$this->to = $to;
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

