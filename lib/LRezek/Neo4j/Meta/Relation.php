<?php
/**
 * Contains the Relation meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */
namespace LRezek\Neo4j\Meta;
use Doctrine\Common\Annotations\Reader as AnnotationReader;
use LRezek\Neo4j\Exception;

class Relation extends GraphElement
{
    private $start;
    private $end;

    //Repository class
    private $repositoryClass = 'LRezek\\Neo4j\\Repository';

    /**
     * Sets the repository class for relations.
     *
     * @param string $repositoryClass The repository class to use.
     */
    function setRepositoryClass($repositoryClass)
    {
        if ($repositoryClass)
        {
            $this->repositoryClass = $repositoryClass;
        }
    }

    /**
     * Retrieves the repository class for relations.
     *
     * @return string The name of the repository class.
     */
    function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    /*
     * Loops through properties looking for a end node.
     */
	function loadEnd($reader, $properties)
	{
        foreach ($properties as $property) {
            $prop = new Property($reader, $property);

            if($prop->isEnd()) {
                $this->setEnd($prop);
            }
		}
	}

    /*
     * Looks through properties looking for a start node.
     */
    function loadStart($reader, $properties)
    {
        foreach ($properties as $property) {
            $prop = new Property($reader, $property);

            if($prop->isStart()) {
                $this->setStart($prop);
            }
        }
    }

    function getStart()
    {
        return $this->start;
    }

    function setStart(Property $property)
    {
        if ($this->start) {
            throw new Exception("Class {$this->getName()} contains multiple targets for @Start");
        }

        $this->start = $property;
    }

    function getEnd()
    {
        return $this->end;
    }

    function setEnd(Property $property)
    {
        if ($this->end) {
            throw new Exception("Class {$this->getName()} contains multiple targets for @End");
        }

        $this->end = $property;
    }

    function validate()
    {
        //If both start and end contain something
        if($this->end && $this->start)
        {
            //Validate the parent (check the primary key)
            parent::validate();
        }

        //A node is missing
        else
        {
            //Start annotation is missing
            if($this->end)
            {
                throw new Exception("Class {$this->getName()} contains no targets for @Start");
            }

            //End annotation is missing
            else
            {
                throw new Exception("Class {$this->getName()} contains no targets for @End");
            }
        }

    }

    /**
     * Find properties based on a method name.
     *
     * This overrides graph elements findProperty, in order to allow for finding the start/end properties.
     *
     * @param string $name Method name.
     * @return Property|null Property if found, otherwise null.
     */
    function findProperty($name)
    {
        $property = Reflection::getProperty($name);

        if ($this->start->matches(substr($name, 3), $property)) {
            return $this->start;
        }

        if ($this->end->matches(substr($name, 3), $property)) {
            return $this->end;
        }

        return parent::findProperty($name);
    }
}
