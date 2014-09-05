<?php
/**
 * Contains the Repository meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP\Meta;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use LRezek\Neo4PHP\Exception;

/**
 * Meta repository class
 *
 * This class is responsible for parsing annotations and getting meta data based on them.
 *
 * @package LRezek\Neo4PHP\Meta
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
        if ($annotationReader instanceof Reader)
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
        if (! isset($this->metas[$className]))
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
     * @throws \LRezek\Neo4PHP\Exception Thrown if the class is not a node or relation.
     */
    private function findMeta($className)
    {
        $class = new \ReflectionClass($className);

        //If it's a proxy class, use the parent for meta
        if ($class->implementsInterface('LRezek\\Neo4PHP\\Proxy\\Entity'))
        {
            $class = $class->getParentClass();
        }

        //Handle nodes
        if ($entity = $this->reader->getClassAnnotation($class, 'LRezek\\Neo4PHP\\Annotation\\Node'))
        {
			return $this->handleNode($entity, $class);
		}

        //Handle Relations
        elseif ($entity = $this->reader->getClassAnnotation($class, 'LRezek\\Neo4PHP\\Annotation\\Relation'))
        {
			return $this->handleRelation($entity, $class);
		}

        //Unknown annotation
        else
        {
            $className = $class->getName();
			throw new Exception("Class $className is not declared as a node or relation.");
        }
	}

    /**
     * Handles meta loading for node objects.
     *
     * @param mixed $node The node object.
     * @param \ReflectionClass $class A reflection class of the node class.
     * @return Node The node meta object.
     */
    private function handleNode($node, $class)
	{
        $object = new Node($class->getName());

		$object->setRepositoryClass($node->repositoryClass);
        $object->loadProperties($this->reader, $class->getProperties());
        $object->validate();

        return $object;
    }

    /**
     * Handles meta loading for relation objects.
     *
     * @param mixed $relation The relation object.
     * @param \ReflectionClass $class A reflection class of the relation class.
     * @return Relation The relation meta object.
     */
	private function handleRelation($relation, $class)
	{
        $object = new Relation($class->getName());

        $object->setRepositoryClass($relation->repositoryClass);
        $object->loadProperties($this->reader, $class->getProperties());
		$object->loadEnd($this->reader, $class->getProperties());
        $object->loadStart($this->reader, $class->getProperties());
        $object->validate();

        return $object;
    }
}

