<?php
/**
 * Contains the Repository meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Meta;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use LRezek\Arachnid\Exception;

/**
 * Meta repository class
 *
 * This class is responsible for parsing annotations and getting meta data based on them.
 *
 * @package Arachnid
 * @subpackage Meta
 */
class Repository
{
    /** @var \Doctrine\Common\Annotations\AnnotationReader The annotation reader to use for annotations.*/
    private $reader;

    /** @var array Storage for parsed meta information so you don't have to re-parse every time. */
    private $metas = array();

    /**
     * Initializes the meta repository.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $annotationReader The annotation reader to use.
     */
    function __construct($annotationReader = null)
    {
        //Initialize annotation reader
        if($annotationReader instanceof Reader)
        {
            $this->reader = $annotationReader;
        }

        else
        {
            $this->reader = new AnnotationReader;
        }
    }

    /**
     * Get the meta info for the class specified by $className
     *
     * @param string $className The class name to get meta for.
     * @return mixed The meta information for the class.
     */
    function fromClass($className)
    {
        if(!isset($this->metas[$className]))
        {
            $this->metas[$className] = $this->findMeta($className, $this);
        }

        return $this->metas[$className];
    }

    /**
     * Does the actual annotation parsing to get meta information for a given class.
     *
     * @param string $className The class name to get meta for.
     * @return Node|Relation A node/relation meta object.
     * @throws \LRezek\Arachnid\Exception Thrown if the class is not a node or relation.
     */
    private function findMeta($className)
    {
        $class = new \ReflectionClass($className);

        //If it's a proxy class, use the parent for meta
        if($class->implementsInterface('LRezek\\Arachnid\\Proxy\\Entity'))
        {
            $class = $class->getParentClass();
        }

        $node = $this->reader->getClassAnnotation($class, 'LRezek\\Arachnid\\Annotation\\Node');
        $relation = $this->reader->getClassAnnotation($class, 'LRezek\\Arachnid\\Annotation\\Relation');

        //Throw an error if it has both annotations
        if($node && $relation)
        {
            throw new Exception("Class $className is defined as both a node and relation.");
        }

        //Handle nodes
        if($node)
        {
            //Save the node to common object
            $entity = $node;

            //Create the node
            $object = new Node($class->getName());
        }

        //Handle Relations
        else if($relation)
        {
            //Save the relation to a common object
            $entity = $relation;

            //Create the relation
            $object = new Relation($class->getName());
        }

        //Unknown annotation
        else
        {
            $className = $class->getName();
            throw new Exception("Class $className is not declared as a node or relation.");
        }

        //Set the objects repo class
        $object->setRepositoryClass($entity->repositoryClass);

        //Load object properties, and validate it
        $object->loadProperties($this->reader, $class->getProperties());
        $object->validate();

        return $object;
    }
}

