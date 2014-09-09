<?php
namespace LRezek\Arachnid\Tests\Entity;
use LRezek\Arachnid\Annotation as OGM;

/**
 * @OGM\Relation
 */
class FriendsWithUnderscoreNotation
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
	 */
	protected $to;

    /**
     * @OGM\Property
     * @OGM\Index
     */
    protected $since;


    function get_from()
	{
		return $this->from;
	}

	function set_from(UserUnderscoreNotation $from)
	{
		$this->from = $from;
	}

	function get_to()
	{
		return $this->to;
	}

	function set_to(UserUnderscoreNotation $to)
	{
		$this->to = $to;
	}

	function get_since()
	{
		return $this->since;
	}

	function set_since($since)
	{
		$this->since = $since;
	}

    function get_id()
    {
        return $this->id;
    }

    function set_id($id)
    {
        $this->id = $id;
    }
}

