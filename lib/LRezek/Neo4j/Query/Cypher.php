<?php

namespace LRezek\Neo4j\Query;
use Everyman\Neo4j\Node;
use Everyman\Neo4j\Relationship;
use Lrezek\Neo4j\EntityManager;

class Cypher
{
    private $em;
    private $start = array();
    private $match = array();
    private $return = array();
    private $where = array();
    private $order = array();
    private $limit;
    private $processor;

    function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->processor = new ParameterProcessor(ParameterProcessor::CYPHER);
    }

    function start($string)
    {
        $this->start = array_merge($this->start, func_get_args());
        return $this;
    }

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

    function startWithQuery($name, $index, $query)
    {
        $this->start("$name = node:`$index`('$query')");

        return $this;
    }

    function startWithLookup($name, $index, $key, $value)
    {
        $this->start("$name = node:`$index`($key = :{$name}_{$key})");
        $this->set("{$name}_{$key}", $value);

        return $this;
    }

    function match($string)
    {
        $this->match = array_merge($this->match, func_get_args());
        return $this;
    }

    function end($string)
    {
        $this->return = array_merge($this->return, func_get_args());
        return $this;
    }

    function where($string)
    {
        $this->where = array_merge($this->where, func_get_args());
        return $this;
    }

    function order($string)
    {
        $this->order = array_merge($this->order, func_get_args());
        return $this;
    }

    function limit($limit)
    {
        $this->limit = (int) $limit;
        return $this;
    }

    function set($name, $value)
    {
        $this->processor->setParameter($name, $value);

        return $this;
    }

    function getOne()
    {
        $result = $this->execute();
        if (isset($result[0])) {
            return $this->convertValue($result[0][0]);
        }
    }

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
