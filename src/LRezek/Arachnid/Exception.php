<?php
/**
 * Contains the Exception class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid;

use Everyman\Neo4j\Query;

/**
 * Extends base PHP exception to add the ability to store query information.
 *
 * @package Arachnid
 */
class Exception extends \Exception
{
    /** @var Query information storage */
    private $query;

    /**
     * Create the exception with a message, code, previous exception, and query info.
     *
     * @param null $message Message to display.
     * @param int $code Exception code.
     * @param \Exception $previous Original exception object.
     * @param Query $query Query information.
     */
    public function __construct($message = null, $code = 0, \Exception $previous = null, Query $query = null)
    {
        parent::__construct($message, $code, $previous);
        $this->setQuery($query);
    }

    /**
     * Returns the query that caused the exception.
     *
     * @return String The query that caused this exception.
     * @api
     */
    public function getQuery() 
    {
        //Grab the query string from the everyman query.
        return $this->query->getQuery();
    }

    /**
     * Alternate notation for getQuery().
     *
     * @return Query
     * @api
     */
    public function get_query()
    {
        return $this->getQuery();
    }

    /**
     * Set the query for this exception.
     *
     * @param string $query The query to attach to this exception.
     * @return Exception Return this exception.
     */
    public function setQuery($query) 
    {
        $this->query = $query;
        return $this;
    }
}

