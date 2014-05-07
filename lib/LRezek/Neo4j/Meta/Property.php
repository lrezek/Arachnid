<?php
/**
 * Contains the Property meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4j\Meta;

/**
 * Defines meta for a Property.
 *
 * This is a meta class that defines the meta for a Property. It does NOT contain actual property values, just a list of
 * property types (AUTO, PROPERTY, INDEX, START, END), the format of the property and the name of it. Properties are
 * constructed with an AnnotationReader, so that they can read what sort of property they should act as.
 *
 * @package Neo4j\Meta
 */
class Property
{

    const AUTO = 'LRezek\\Neo4j\\Annotation\\Auto';
    const PROPERTY = 'LRezek\\Neo4j\\Annotation\\Property';
    const INDEX = 'LRezek\\Neo4j\\Annotation\\Index';

    //Relation types
    const START = 'LRezek\\Neo4j\\Annotation\\Start';
    const END = 'LRezek\\Neo4j\\Annotation\\End';

    private $reader;                //Annotation reader
    private $property;              //Actual Property Object
    private $name;                  //Name of the property
    private $format = 'scalar';     //Default format of property

    /**
     * Constructor. Saves the annotation reader and the actual property.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader Annotation reader to use.
     * @param \LRezek\Neo4j\Annotation\Property $property The property to use.
     */
    function __construct($reader, $property)
    {
        //Take the reader
        $this->reader = $reader;

        //Take the actual property value
        $this->property = $property;


        if ($this->isProperty())
        {
            //Get the properties name
            $this->name = $property->getName();
        }

        else
        {
            //As far as we know only relation list are collections with names we can 'normalize'
            $this->name = Reflection::singularizeProperty($property->getName());
        }

        //Make the property accessible if it is private
        $property->setAccessible(true);
    }

    /**
     * Checks if this property is a primary key.
     *
     * @return bool
     */
    function isPrimaryKey()
    {
        return !! $this->reader->getPropertyAnnotation($this->property, self::AUTO);
    }

    /**
     * Checks if this property is a start node.
     *
     * @return bool
     */
    function isStart()
    {
        return !! $this->reader->getPropertyAnnotation($this->property, self::START);
    }

    /**
     * Checks if this property is a end node.
     *
     * @return bool
     */
    function isEnd()
    {
        return !! $this->reader->getPropertyAnnotation($this->property, self::END);
    }

    /**
     * Checks if this property is indeed a property.
     *
     * @return bool
     */
    function isProperty()
    {
        //Use the annotation reader to determine if this is an actual property.
        if ($annotation = $this->reader->getPropertyAnnotation($this->property, self::PROPERTY))
        {
            //Set the format (Date, JSON, scalar, etc)
            $this->format = $annotation->format;

            //This is a property
            return true;
        }

        else
        {
            //Not a property
            return false;
        }
    }

    /**
     * Checks if this property is indexed.
     *
     * @return bool
     */
    function isIndexed()
    {
        return !! $this->reader->getPropertyAnnotation($this->property, self::INDEX);
    }

    /**
     * Checks if this property is private.
     *
     * @return bool
     */
    function isPrivate()
    {
        return $this->property->isPrivate();
    }

    /**
     * Gets this property's value in the given entity.
     *
     * @param Relation|Node $entity The entity to get the value from.
     * @return mixed The property value.
     */
    function getValue($entity)
    {
        $raw = $this->property->getValue($entity);

        switch ($this->format)
        {
            case 'scalar':
                return $raw;

            case 'array':
                return serialize($raw);

            case 'json':
                return json_encode($raw);

            case 'date':

                if ($raw)
                {
                    $value = clone $raw;
                    $value->setTimezone(new \DateTimeZone('UTC'));
                    return $value->format('Y-m-d H:i:s');
                }

                else
                {
                    return null;
                }
        }
    }

    /**
     * Sets this property's value in the given entity.
     *
     * @param Relation|Node $entity The entity to set the property of.
     * @param mixed $value The value to set it to.
     */
    function setValue($entity, $value)
    {
        switch ($this->format) {
        case 'scalar':
            $this->property->setValue($entity, $value);
            break;
        case 'array':
            $this->property->setValue($entity, unserialize($value));
            break;
        case 'json':
            $this->property->setValue($entity, json_decode($value, true));
            break;
        case 'date':
            $date = null;
            if ($value) {
                $date = new \DateTime($value . ' UTC');
                $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            }

            $this->property->setValue($entity, $date);
            break;
        }
    }

    /**
     * Get the property's name.
     *
     * @return string The property's name.
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Checks if a supplied name matches this property's name.
     *
     * @param mixed $names List of names to check.
     * @return bool
     */
    function matches($names)
    {
        foreach (func_get_args() as $name)
        {
            if (0 === strcasecmp($name, $this->name)
                || 0 === strcasecmp($name, $this->property->getName())
                || 0 === strcasecmp($name, Reflection::singularizeProperty($this->property->getName()))
            )
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the format of the property.
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }
}

