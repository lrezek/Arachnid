<?php
/**
 * Contains the Relation meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */
namespace LRezek\Arachnid\Meta;
use Doctrine\Common\Annotations\Reader as AnnotationReader;
use LRezek\Arachnid\Exception;

/**
 * Defines meta for a Relation.
 *
 * This is a meta class that defines the meta for a Relation. It is a meta object that extends \LRezek\Arachnid\Meta\GraphElement,
 * meaning it only contains property information for a relation, retrieved through an annotation reader. It does NOT contain
 * actual property values, just a list of indexed/non-indexed properties and the primary key for a class that has the
 * correct relation annotations. It also contains the repository class to use when querying for objects of this type.
 *
 * @package Arachnid
 * @subpackage Meta
 */
class Relation extends GraphElement
{
    /** @var Property Stores the property to use as the start of the relation.*/
    private $start;

    /** @var Property Stores the property to use as the end of the relation.*/
    private $end;

    /** @var string Stores the repository class to be used when querying for the relation.*/
    private $repositoryClass = 'LRezek\\Arachnid\\Repository';

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

    /**
     * Gets the start property of the relation.
     *
     * @return Property The start property.
     */
    function getStart()
    {
        return $this->start;
    }

    /**
     * Sets the start property of the relation.
     *
     * @param Property $property The property to set the start to.
     * @throws \LRezek\Arachnid\Exception Thrown if the relation already has a start.
     */
    function setStart(Property $property)
    {
        if($this->start)
        {
            throw new Exception("Class {$this->getName()} contains multiple targets for @Start.");
        }

        $this->start = $property;
    }

    /**
     * Gets the end property of the relation.
     *
     * @return Property The end property.
     */
    function getEnd()
    {
        return $this->end;
    }

    /**
     * Sets the end property of the relation.
     *
     * @param Property $property The property to set the end to.
     * @throws \LRezek\Arachnid\Exception Thrown if the relation already has a end.
     */
    function setEnd(Property $property)
    {
        if($this->end)
        {
            throw new Exception("Class {$this->getName()} contains multiple targets for @End.");
        }

        $this->end = $property;
    }

    /**
     * Validates the relation object, making sure it has a start and an end property.
     *
     * @throws \LRezek\Arachnid\Exception Thrown if the object does not have a start/end property.
     */
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
                throw new Exception("Class {$this->getName()} contains no targets for @Start.");
            }

            //End annotation is missing
            else
            {
                throw new Exception("Class {$this->getName()} contains no targets for @End.");
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
        //Get the property from the method name
        $property = Reflection::getProperty($name);

        if ($this->start->matches($property))
        {
            return $this->start;
        }

        if ($this->end->matches($property))
        {
            return $this->end;
        }

        return parent::findProperty($name);
    }

    /**
     * Loads the required properties.
     *
     * Loops through properties and saves them based on their annotation, using the annotation reader supplied.
     * Properties are saved in <code>$this->properties</code>, indexed properties are saved in <code>$this->indexedProperties</code>,
     * the primary key is saved in <code>$this->primaryKey</code>, the start property is saved in <code>$this->start</code>, and the
     * end property is saved in <code>$this->end</code>
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader The annotation reader to use.
     * @param \ReflectionProperty[] $properties Array of reflection properties, from <code>reflectionClass->getProperties()</code>.
     */
    public function loadProperties($reader, $properties)
    {
        //Loop through properties
        foreach ($properties as $property)
        {
            $prop = new Property($reader, $property);

            //Load start.
            if($prop->isStart())
            {
                $this->setStart($prop);

            }

            //Load end.
            else if($prop->isEnd())
            {
                $this->setEnd($prop);
            }

            //Load the property (auto, property, indexed)
            $this->loadProperty($prop);
        }
    }
}
