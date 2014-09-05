<?php
/**
 * Contains the GraphElement abstract class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP\Meta;
use LRezek\Neo4PHP\Exception;

/**
 * Defines basic meta for a graph element.
 *
 * This is a meta class that defines the meta for a general graph element. This is the parent class for both node and
 * relation meta objects. It is a meta object, meaning it only contains property information for an class, retrieved
 * through an annotation reader. It does NOT contain actual property values, just a list of indexed/non-indexed
 * properties and the primary key for a class that has the correct annotations.
 *
 * @package Neo4j\Meta
 */
abstract class GraphElement
{

    /** @var string The child class's name.*/
    private $className;
    /** @var \LRezek\Neo4PHP\Meta\Property The graph element's primary key property.*/
    private $primaryKey;
    /** @var \LRezek\Neo4PHP\Meta\Property[] The graph element's properties.*/
    private $properties = array();
    /** @var \LRezek\Neo4PHP\Meta\Property[] The graph element's indexed properties.*/
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
     * Loads the required properties.
     *
     * Loops through properties and saves them based on their annotation, using the annotation reader supplied.
     * Properties are saved in <code>$this->properties</code>, indexed properties are saved in <code>$this->indexedProperties</code>, and the
     * primary key is saved in <code>$this->primaryKey</code>.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader The annotation reader to use.
     * @param mixed[] $properties The properties to load.
     */
    public function loadProperties($reader, $properties)
	{
        //Loop through properties
        foreach ($properties as $property)
        {
            $prop = new Property($reader, $property);

            //Check for primary key
            if ($prop->isPrimaryKey())
            {
                $this->setPrimaryKey($prop);
            }

            //Make sure it's a property
            elseif ($prop->isProperty())
            {
                $this->properties[] = $prop;

                //Check for indexed properties
                if ($prop->isIndexed())
                {
                    $this->indexedProperties[] = $prop;
                }
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
     * @return \LRezek\Neo4PHP\Meta\Property[] The array of indexed properties.
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
     * @return \LRezek\Neo4PHP\Meta\Property[] The array of properties.
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
     * Finds property by name.
     *
     * @param string $name
     * @return \LRezek\Neo4PHP\Meta\Property|null The property, or null if it's not found.
     */
    function findProperty($name)
    {
        //Get rid of get/set and get the singularized prop name
        $property = Reflection::getProperty($name);

        foreach ($this->properties as $p)
        {
            if ($p->matches(substr($name, 3), $property)) {
                return $p;
            }
        }

    }

    /**
     * Sets the primary key to the specified property.
     *
     * This sets the primary key to the specified property, unless a property already exists as the primary key. If
     * that is the case, an exception is thrown.
     *
     * @param \LRezek\Neo4PHP\Meta\Property $property The property to set as primary key.
     * @throws \LRezek\Neo4PHP\Exception "Class contains multiple targets for @auto" exception.
     */
    function setPrimaryKey(Property $property)
    {
        if ($this->primaryKey)
        {
             throw new Exception("Class {$this->className} contains multiple targets for @Auto");
        }

        $this->primaryKey = $property;
    }

    /**
     * Ensures the Graph Element contains a property designated as the private key.
     *
     * This validates that the graph element's meta contains a property designated as the primary key
     * (has the @auto annotation).
     *
     * @throws \LRezek\Neo4PHP\Exception "Class contains no @auto property" exception.
     */
    function validate()
    {
        if (! $this->primaryKey)
        {
             throw new Exception("Class {$this->className} contains no @Auto property");
        }

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

