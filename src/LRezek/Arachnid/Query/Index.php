<?php
/**
 * Contains the Index class, which is responsible for constructing index queries.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */


namespace LRezek\Arachnid\Query;

/**
 * Handles construction of Index queries.
 *
 * @package Arachnid
 * @subpackage Query
 */
class Index
{
    /** @var string The actual query. */
    private $query = '';

    /**
     * Adds a query term to the query, with AND. This should only be used by Arachnid, as it takes a term of ANDS and
     * hands it to everyman index->query.
     *
     * @param string $term The term to add.
     * @param string $value The value to add for the term.
     */
    public function addAndTerm($term, $value)
    {
        //Trim spaces from the term
        $value = trim($value);

        //If the query isn't empty, add an AND
        if($this->query !== '')
        {
            $this->query .= ' AND ';
        }

        //Add the term to the query
        $this->query .= $term.':'.$value;
    }

    /**
     * Gets the query string.
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }
}

?> 