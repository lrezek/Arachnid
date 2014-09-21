<?php
/**
 * Contains the GraphElement abstract class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Meta;
use LRezek\Arachnid\Exception;

/**
 * Defines basic meta for a graph element.
 *
 * This is a meta class that defines the meta for a general graph element. This is the parent class for both node and
 * relation meta objects. It is a meta object, meaning it only contains property information for an class, retrieved
 * through an annotation reader. It does NOT contain actual property values, just a list of indexed/non-indexed
 * properties and the primary key for a class that has the correct annotations.
 *
 * @package Arachnid
 * @subpackage Meta
 */
abstract class GraphElement
{

    /** @var string The child class's name.*/
    private $className;
    /** @var \LRezek\Arachnid\Meta\Property The graph element's primary key property.*/
    private $primaryKey;
    /** @var \LRezek\Arachnid\Meta\Property[] The graph element's properties.*/
    private $properties = array();
    /** @var \LRezek\Arachnid\Meta\Property[] The graph element's indexed properties.*/
    private $indexedProperties = array();

    /**
     * Class constructor.
     *
     * Saves the class name of the extending class.
     *
     * @param string $className The class name that is extending this class.
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Loads a property into this graph element.
     *
     * This loads a property object into the graph element. If the property is the primary key, it is remembered in
     * <code>$this->primaryKey</code>, if it is a property, it is saved in <code>$this->properties</code>. If it is indexed,
     * it is saved to <code>$this->indexedProperties</code>
     *
     * @param Property $prop The property to load.
     */
    public function loadProperty($prop)
    {
        //Save primary key
        if($prop->isPrimaryKey())
        {
            $this->setPrimaryKey($prop);
        }

        //Save property
        else if($prop->isProperty())
        {
            $this->properties[] = $prop;

            //Check for indexed properties
            if ($prop->isIndexed())
            {
                $this->indexedProperties[] = $prop;
            }
        }
    }

    /**
     * Returns the name of the class extending this class.
     *
     * This returns the name of the class that is extending GraphElement. This name is set in the constructor.
     *
     * @return string The child classes name.
     */
    function getName()
    {
        return $this->className;
    }

    /**
     * Returns the indexed properties of this graph element (node or relation).
     *
     * This returns an array of indexed properties, which are loaded in loadProperties using an annotation reader.
     *
     * @return \LRezek\Arachnid\Meta\Property[] The array of indexed properties.
     */
    function getIndexedProperties()
    {
        return $this->indexedProperties;
    }

    /**
     * Returns the properties of this graph element (node or relation).
     *
     * This returns an array of properties, which are loaded in loadProperties using an annotation reader.
     *
     * @return \LRezek\Arachnid\Meta\Property[] The array of properties.
     */
    function getProperties()
    {
        return $this->properties;
    }

    /**
     * Returns the classes primary key.
     * @return mixed
     */
    function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * Finds property by method name.
     *
     * @param string $name
     * @return \LRezek\Arachnid\Meta\Property|null The property, or null if it's not found.
     */
    function findProperty($name)
    {
        //Get rid of get/set and get the singularized prop name
        $property = Reflection::getProperty($name);

        foreach ($this->properties as $p)
        {
            if ($p->matches($property))
            {
                return $p;
            }
        }

        return null;
    }

    /**
     * Sets the primary key to the specified property.
     *
     * This sets the primary key to the specified property, unless a property already exists as the primary key. If
     * that is the case, an exception is thrown.
     *
     * @param \LRezek\Arachnid\Meta\Property $property The property to set as primary key.
     * @throws \LRezek\Arachnid\Exception "Class contains multiple targets for @auto" exception.
     */
    function setPrimaryKey(Property $property)
    {
        if ($this->primaryKey)
        {
             throw new Exception("Class {$this->className} contains multiple targets for @Auto.");
        }

        $this->primaryKey = $property;
    }

    /**
     * Ensures the Graph Element contains a property designated as the private key, and does some other annotation checks.
     *
     * This validates that the graph element's meta contains a property designated as the primary key
     * (has the @auto annotation). It also validates that a node does not have a start or end annotation.
     *
     * @throws \LRezek\Arachnid\Exception "Class contains no @auto property" exception.
     */
    function validate()
    {
        if (!$this->primaryKey)
        {
             throw new Exception("Class {$this->className} contains no @Auto property.");
        }

        //Validate that there is only a node or relation annotation

    }

    /**
     * Gets the proxy class for this graph element.
     *
     * Creates a proxy class name for a graph element. The name is prepended with <code>'neo4jproxy'</code>, and is
     * basically the regular class name with <code>'\\'</code> sections replaced with underscores.
     *
     * @return string The proxy classes name.
     */
    function getProxyClass()
    {
        return 'neo4jProxy' . str_replace('\\', '_', $this->className);
    }
}

