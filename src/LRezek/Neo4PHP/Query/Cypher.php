<?php
/**
 * Contains the Cypher class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Lrezek\Neo4PHP\EntityManager;

/**
 * Handles construction and execution of cypher queries.
 *
 * This class handles the construction and execution of cypher queries. Most of the methods in this class return the
 * class itself, allowing for cascading method calls.
 *
 * @package Neo4PHP
 * @subpackage Query
 */
class Cypher
{
    /** @var \LRezek\Neo4PHP\EntityManager The entity manager. */
    private $em;

    /** @var array Array fo start clauses. */
    private $start = array();

    /** @var array Array of match clauses. */
    private $match = array();

    /** @var array Array of return clauses. */
    private $return = array();

    /** @var array Array of where clauses. */
    private $where = array();

    /** @var array Array of order clauses. */
    private $order = array();

    /** @var  int Mimics the limit clause. */
    private $limit;

    /** @var \LRezek\Neo4PHP\Query\ParameterProcessor The parameter processor to use. */
    private $processor;

    /**
     * Initializes the cypher query with an entity manager and a nbew parameter processor.
     *
     * @param EntityManager $em The entity manager to use.
     */
    function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->processor = new ParameterProcessor(ParameterProcessor::CYPHER);
    }

    /**
     * Adds parameters to the queries "start" clause.
     *
     * @param string $string The string to add to the start clause.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function start($string)
    {
        $this->start = array_merge($this->start, func_get_args());
        return $this;
    }

    /**
     * Adds nodes to the queries "start" clause.
     *
     * @param string $name The name to use for the nodes.
     * @param mixed $nodes A single (or multiple) node to start on.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function startWithNode($name, $nodes)
    {
        if (! is_array($nodes)) {
            $nodes = array($nodes);
        }

        $parts = array();
        foreach ($nodes as $key => $node) {
            $fullKey = $name . '_' .$key;

            $parts[] = ":$fullKey";
            $this->set($fullKey, $node);
        }

        $parts = implode(', ', $parts);
        $this->start("$name = node($parts)");
        
        return $this;
    }

    /**
     * Adds a node query to the queries "start" clause.
     *
     * @param string $name The name to use for the node.
     * @param string $index The index of the requested start node.
     * @param string $query The query parameters to use for the node.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function startWithQuery($name, $index, $query)
    {
        $this->start("$name = node:`$index`('$query')");

        return $this;
    }

    /**
     * Adds a node lookup to the queries "start" clause.
     *
     * @param string $name The name to use for the node.
     * @param string $index The index of the requested start node.
     * @param string $key The key to look for.
     * @param string $value The value of the key for the lookup.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function startWithLookup($name, $index, $key, $value)
    {
        $this->start("$name = node:`$index`($key = :{$name}_{$key})");
        $this->set("{$name}_{$key}", $value);

        return $this;
    }

    /**
     * Adds parameters to the queries "match" clause.
     *
     * @param string $string The string to add to the match clause.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function match($string)
    {
        $this->match = array_merge($this->match, func_get_args());
        return $this;
    }

    /**
     * Adds parameters to the queries "end" clause.
     *
     * @param string $string The string to add to the end clause.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function end($string)
    {
        $this->return = array_merge($this->return, func_get_args());
        return $this;
    }

    /**
     * Adds parameters to the queries "where" clause.
     *
     * @param string $string The string to add to the where clause.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function where($string)
    {
        $this->where = array_merge($this->where, func_get_args());
        return $this;
    }

    /**
     * Adds parameters to the queries "order" clause.
     *
     * @param string $string The string to add to the order clause.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function order($string)
    {
        $this->order = array_merge($this->order, func_get_args());
        return $this;
    }

    /**
     * Adds a "limit" to the returned results.
     *
     * @param int $limit How many entries to limit the results to.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    /**
     * Sets named query parameters.
     *
     * @param string $name The name of the parameter.
     * @param mixed $value The value of the parameter.
     * @return $this Returns this class to allow for cascading method calls.
     */
    function set($name, $value)
    {
        $this->processor->setParameter($name, $value);

        return $this;
    }

    /**
     * Executes the query and returns one result.
     *
     * @return mixed|null The query result.
     */
    function getOne()
    {
        $result = $this->execute();

        if (isset($result[0]))
        {
            return $this->convertValue($result[0][0]);
        }
    }

    /**
     * Executes the query and returns a list of results, as entities.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection The query result.
     */
    function getList()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        //Convert all of them to entities
        foreach ($result as $row)
        {
            $list->add($this->convertValue($row[0]));
        }

        return $list;
    }

    /**
     * Executes the query and returns a array collection of assoc. arrays, without converting them to entities.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection The query result.
     */
    function getResult()
    {
        $result = $this->execute();
        $list = new \Doctrine\Common\Collections\ArrayCollection;

        //Loop through results
        foreach ($result as $row)
        {
            $entry = array();

            //Loop through entities attributes (or relations)
            foreach ($row as $key => $value)
            {
                $entry[$key] = $this->convertValue($value);
            }

            //Add to list
            $list->add($entry);
        }

        return $list;
    }

    /**
     * Executes the query.
     *
     * @return \Everyman\Neo4j\Query\ResultSet The query result.
     */
    private function execute()
    {
        $query = '';

        //Add the start (it it's set)
        if(count($this->start))
        {
            $query .= 'start ' . implode(', ', $this->start) . PHP_EOL;
        }

        //Add matches
        if (count($this->match))
        {
            $query .= 'match ' . implode(', ', $this->match) . PHP_EOL;
        }

        //Add where's
        if (count($this->where)) {
            $query .= 'where (' . implode(') AND (', $this->where) . ')' . PHP_EOL;
        }

        //Add returns
        $query .= 'return ' . implode(', ', $this->return) . PHP_EOL;

        if (count($this->order)) {
            $query .= 'order by ' . implode(', ', $this->order) . PHP_EOL;
        }

        if ($this->limit) {
            $query .= 'limit ' . $this->limit . PHP_EOL;
        }

        $this->processor->setQuery($query);
        $parameters = $this->processor->process();

        return $this->em->cypherQuery($this->processor->getQuery(), $parameters);
    }

    /**
     * Converts raw query results to node/relation entities.
     *
     * @param mixed $value One query result.
     * @return Node|Relationship The entity.
     */
    private function convertValue($value)
    {
        if ($value instanceof Node)
        {
            return $this->em->loadNode($value);
        }

        elseif ($value instanceof Relationship)
        {
            return $this->em->loadRelation($value);
        }

        else
        {
            return $value;
        }
    }
}
