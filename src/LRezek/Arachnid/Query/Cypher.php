<?php
/**
 * Contains the Cypher class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Query;
use Doctrine\Common\Collections\ArrayCollection;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Lrezek\Arachnid\Arachnid;

/**
 * Handles construction and execution of cypher queries.
 *
 * This class handles the construction and execution of cypher queries. Most of the methods in this class return the
 * class itself, allowing for cascading method calls.
 *
 * @package Arachnid
 * @subpackage Query
 */
class Cypher
{
    const STRICT = 'STRICT_MODE';

    /** @var \LRezek\Arachnid\Arachnid The entity manager. */
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

    /** @var array Array of Query parameters. */
    private $parameters = array();

    /** @var string The actual cypher query. */
    private $query = '';

    /**
     * Initializes the cypher query with an entity manager and a nbew parameter processor.
     *
     * @param Arachnid $em The entity manager to use.
     */
    public function __construct(Arachnid $em)
    {
        $this->em = $em;
    }

    /**
     * Adds parameters to the queries "start" clause.
     *
     * @param string $string The string to add to the start clause.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function start($string)
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
     * @api
     */
    public function startWithNode($name, $nodes)
    {
        if (! is_array($nodes))
        {
            $nodes = array($nodes);
        }

        $parts = array();
        foreach ($nodes as $key => $node)
        {
            $fullKey = $name . '_' .$key;

            $parts[] = ":$fullKey";
            $this->set($fullKey, $node);
        }

        $parts = implode(', ', $parts);
        $this->start("$name = node($parts)");
        
        return $this;
    }

    /**
     * Alternate notation for startWithNode().
     *
     * @param string $name The name to use for the nodes.
     * @param mixed $nodes A single (or multiple) node to start on.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function start_with_node($name, $nodes)
    {
        return $this->startWithNode($name, $nodes);
    }

    /**
     * Adds a node query to the queries "start" clause.
     *
     * @param string $name The name to use for the node.
     * @param string $index The index of the requested start node.
     * @param string $query The query parameters to use for the node.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function startWithQuery($name, $index, $query)
    {
        $this->start("$name = node:`$index`('$query')");

        return $this;
    }

    /**
     * Alternate notation for startWithQuery().
     *
     * @param string $name The name to use for the node.
     * @param string $index The index of the requested start node.
     * @param string $query The query parameters to use for the node.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function start_with_query($name, $index, $query)
    {
        return $this->startWithQuery($name, $index, $query);
    }

    /**
     * Adds a node lookup to the queries "start" clause.
     *
     * @param string $name The name to use for the node.
     * @param string $index The index of the requested start node.
     * @param string $key The key to look for.
     * @param string $value The value of the key for the lookup.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function startWithLookup($name, $index, $key, $value)
    {
        $this->start("$name = node:`$index`($key = :{$name}_{$key})");
        $this->set("{$name}_{$key}", $value);

        return $this;
    }

    /**
     * Alternate notation for startWithLookup().
     *
     * @param string $name The name to use for the node.
     * @param string $index The index of the requested start node.
     * @param string $key The key to look for.
     * @param string $value The value of the key for the lookup.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function start_with_lookup($name, $index, $key, $value)
    {
        return $this->startWithLookup($name, $index, $key, $value);
    }

    /**
     * Adds parameters to the queries "match" clause.
     *
     * @param string $string The string to add to the match clause.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function match($string)
    {
        $this->match = array_merge($this->match, func_get_args());
        return $this;
    }

    /**
     * Adds parameters to the queries "end" clause.
     *
     * @param string $string The string to add to the end clause.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function end($string)
    {
        $this->return = array_merge($this->return, func_get_args());
        return $this;
    }

    /**
     * Adds parameters to the queries "where" clause.
     *
     * @param string $string The string to add to the where clause.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function where($string)
    {
        $this->where = array_merge($this->where, func_get_args());
        return $this;
    }

    /**
     * Adds parameters to the queries "order" clause.
     *
     * @param string $string The string to add to the order clause.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function order($string)
    {
        $this->order = array_merge($this->order, func_get_args());
        return $this;
    }

    /**
     * Adds a "limit" to the returned results.
     *
     * @param int $limit How many entries to limit the results to.
     * @return $this Returns this class to allow for cascading method calls.
     * @api
     */
    public function limit($limit)
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
     * @api
     */
    public function set($name, $value)
    {
        //If it's an object, save its ID as the parameter
        if(is_object($value) && method_exists($value, 'getId'))
        {
            $this->parameters[$name] = $value->getId();
        }

        //Just save the parameter
        else
        {
            $this->parameters[$name] = $value;
        }

        return $this;
    }

    /**
     * Executes the query and returns one result.
     *
     * @return mixed|null The query result.
     * @api
     */
    public function getOne()
    {
        $result = $this->execute();

        if (isset($result[0]))
        {
            return $this->convertValue($result[0][0]);
        }

        return null;
    }

    /**
     * Alternate notation for getOne().
     *
     * @return mixed|null The query result.
     * @api
     */
    public function get_one()
    {
        return $this->getOne();
    }

    /**
     * Executes the query and returns a list of results, as entities.
     *
     * @return ArrayCollection The query result.
     * @api
     */
    public function getList()
    {
        $result = $this->execute();
        $list = new ArrayCollection;

        //Convert all of them to entities
        foreach ($result as $row)
        {
            $list->add($this->convertValue($row[0]));
        }

        return $list;
    }

    /**
     * Alternate notation for getList().
     *
     * @return ArrayCollection The query result.
     * @api
     */
    public function get_list()
    {
        return $this->getList();
    }

    /**
     * Executes the query and returns a array collection of assoc. arrays, without converting them to entities.
     *
     * @return ArrayCollection The query result.
     * @api
     */
    public function getResult()
    {
        $result = $this->execute();
        $list = new ArrayCollection;

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
     * Alternate notation for getResult().
     *
     * @return ArrayCollection The query result.
     * @api
     */
    public function get_result()
    {
        return $this->getResult();
    }

    /**
     * Executes the query.
     *
     * @return \Everyman\Neo4j\Query\ResultSet The query result.
     */
    private function execute()
    {
        //Add the start (it it's set)
        if(count($this->start))
        {
            $this->query .= 'start ' . implode(', ', $this->start) . PHP_EOL;
        }

        //Add matches
        if(count($this->match))
        {
            $this->query .= 'match ' . implode(', ', $this->match) . PHP_EOL;
        }

        //Add where's
        if(count($this->where))
        {
            $this->query .= 'where (' . implode(') AND (', $this->where) . ')' . PHP_EOL;
        }

        //Add returns
        if(count($this->return))
        {
            $this->query .= 'return ' . implode(', ', $this->return) . PHP_EOL;
        }

        if (count($this->order))
        {
            $this->query .= 'order by ' . implode(', ', $this->order) . PHP_EOL;
        }

        if ($this->limit)
        {
            $this->query .= 'limit ' . $this->limit . PHP_EOL;
        }

        //Process the parameters
        $this->parameters = $this->processParameters();

        return $this->em->cypherQuery($this->query, $this->parameters);
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

    /**
     * Processes parameters and returns an array of them afterwards.
     *
     * @return array The processed parameters.
     */
    private function processParameters()
    {
        $parameters = $this->parameters;
        $string = $this->query;

        $string = str_replace('[:', '[;;', $string);

        $parameters = array_filter($parameters, function ($value) use (& $parameters, & $string)
        {
            $key = key($parameters);
            next($parameters);

            if (is_numeric($value))
            {
                $string = str_replace(":$key", $value, $string);
                return false;
            }

            else
            {
                $string = str_replace(":$key", '{' . $key . '}', $string);
                return true;
            }
        });

        $string = str_replace('[;;', '[:', $string);

        $this->query = $string;
        return $parameters;
    }

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

        //Clean it up
        if(strpos($value, ' '))
        {
            $fl = mb_substr($value, 0, 1, 'UTF-8');

            if ($fl != '(')
            {
                $value = '"' . $value . '"';
            }
        }

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
