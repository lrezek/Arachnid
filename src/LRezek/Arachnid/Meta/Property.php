<?php
/**
 * Contains the Property meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Meta;
use LRezek\Arachnid\Exception;

/**
 * Defines meta for a Property.
 *
 * This is a meta class that defines the meta for a Property. It does NOT contain actual property values, just a list of
 * property types (AUTO, PROPERTY, INDEX, START, END), the format of the property and the name of it. Properties are
 * constructed with an AnnotationReader, so that they can read what sort of property they should act as.
 *
 * @package Arachnid
 * @subpackage Meta
 */
class Property
{
    const ANNOTATION_NAMESPACE = 'LRezek\\Arachnid\\Annotation';
    const AUTO = 'LRezek\\Arachnid\\Annotation\\Auto';
    const PROPERTY = 'LRezek\\Arachnid\\Annotation\\Property';
    const INDEX = 'LRezek\\Arachnid\\Annotation\\Index';

    //Relation types
    const START = 'LRezek\\Arachnid\\Annotation\\Start';
    const END = 'LRezek\\Arachnid\\Annotation\\End';

    /** @var \Doctrine\Common\Annotations\AnnotationReader The annotation reader to use.*/
    private $reader;

    /** @var \ReflectionProperty Reflection Property object.*/
    private $property;

    /** @var string The name of the property.*/
    private $name;

    /** @var string The format of the property. */
    private $format = 'scalar';

    /** @var array The annotations attached ot the property. */
    private $annotations = array();

    /**
     * Constructor. Saves the annotation reader and the actual property.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader Annotation reader to use.
     * @param \ReflectionProperty $property A reflection property from the entity class.
     */
    function __construct($reader, $property)
    {
        //Take the reader
        $this->reader = $reader;

        //Take the reflection property
        $this->property = $property;

        //Get the reflection properties name, after normalizing
        $this->name = Reflection::normalizeProperty($property->getName());

        //Save the properties annotations.
        $this->annotations = $this->reader->getPropertyAnnotations($this->property);

        //Validate annotations
        $this->validateAnnotations();

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
        return $this->getAnnotationIndex(self::AUTO) >= 0;
    }

    /**
     * Checks if this property is a start node.
     *
     * @return bool
     */
    function isStart()
    {
        return $this->getAnnotationIndex(self::START) >= 0;
    }

    /**
     * Checks if this property is a end node.
     *
     * @return bool
     */
    function isEnd()
    {
        return $this->getAnnotationIndex(self::END) >= 0;
    }

    /**
     * Checks if this property is indeed a property.
     *
     * @return bool
     */
    function isProperty()
    {
        //Get the annotation index
        $i = $this->getAnnotationIndex(self::PROPERTY);

        //If the property annotation is on here
        if($i >= 0)
        {
            //Set the format (Date, JSON, scalar, etc)
            $this->format = $this->annotations[$i]->format;

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
        return $this->getAnnotationIndex(self::INDEX) >= 0;
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

            //Serialize classes and arrays before putting them in the DB
            case 'class':
            case 'array':
                return serialize($raw);

            //Json encode before putting into DB
            case 'json':
                return json_encode($raw);

            //Format the date correctly before putting in DB
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

        return null;
    }

    /**
     * Sets this property's value in the given entity.
     *
     * @param Relation|Node $entity The entity to set the property of.
     * @param mixed $value The value to set it to.
     */
    function setValue($entity, $value)
    {
        switch ($this->format)
        {
            case 'scalar':
                $this->property->setValue($entity, $value);
                break;

            //Unserialize classes and arrays before putting them back in the entity.
            case 'class':
            case 'array':
                $this->property->setValue($entity, unserialize($value));
                break;

            //Decode Json from DB back into a regular assoc array before putting it into the entity.
            case 'json':
                $this->property->setValue($entity, json_decode($value, true));
                break;

            //Create a date time object out of the db stored date before putting it into the entity.
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
        //Check every argument supplied
        foreach (func_get_args() as $name)
        {
            //Check for any possible match
            if (
                0 === strcasecmp($name, $this->name) ||
                0 === strcasecmp($name, $this->property->getName()) ||
                0 === strcasecmp($name, Reflection::normalizeProperty($this->property->getName()))
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

    /**
     * Validates the annotation combination on a property.
     *
     * @throws Exception If the combination is invalid in some way.
     */
    private function validateAnnotations()
    {
        //Count annotations in the annotation namespace, ignore annotations not in our namespace
        $count = 0;
        foreach($this->annotations as $a)
        {
            //If you find the namespace in the class name, add to count
            if(strrpos(get_class($a), self::ANNOTATION_NAMESPACE) !== false)
            {
                $count++;
            }
        }

        switch($count)
        {
            //0 annotations, just ignore
            case 0:

                return;

            //1 Annotation, it can't be index.
            case 1:

                if($this->getAnnotationIndex(self::INDEX) < 0)
                {
                    //It's not index, return.
                    return;
                }

                throw new Exception("@Index cannot be the only annotation on {$this->name} in {$this->property->getDeclaringClass()->getName()}.");

            //2 Annotations, they have to be index and property.
            case 2:

                if( ($this->getAnnotationIndex(self::PROPERTY) >= 0) && ($this->getAnnotationIndex(self::INDEX) >= 0))
                {
                    //They are index and property, return
                    return;
                }

                break;
        }

        //It didn't fall into any of the categories, must be invalid
        throw new Exception("Invalid annotation combination on {$this->name} in {$this->property->getDeclaringClass()->getName()}.");
    }

    /**
     * Gets the index of a annotation with the value specified, or -1 if it's not in the annotations array.
     *
     * @param String $name The annotation class.
     * @return int The index of the annotation in the annotations array().
     */
    private function getAnnotationIndex($name)
    {
        for($i = 0; $i < count($this->annotations); $i++)
        {
            if($this->annotations[$i] instanceof $name)
            {
                return $i;
            }
        }

        return -1;
    }
}

