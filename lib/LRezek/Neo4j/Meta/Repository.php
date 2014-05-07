<?php
/**
 * Contains the Repository meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4j\Meta;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Annotations\AnnotationReader;
use LRezek\Neo4j\Exception;

/*
 * Meta repository class, this parses annotations and can get meta data based on it
 */
class Repository
{
    //The annotation Reader
    private $reader;

    //Meta info
    private $metas = array();

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

    //Get meta info from class name. $metas is an assoc array by class name
    function fromClass($className)
    {
        if (! isset($this->metas[$className]))
        {
            $this->metas[$className] = $this->findMeta($className, $this);
        }

        return $this->metas[$className];
    }


	private function findMeta($className)
    {
        $class = new \ReflectionClass($className);

        //If it's a proxy class, use the parent for meta
        if ($class->implementsInterface('LRezek\\Neo4j\\Proxy\\Entity'))
        {
            $class = $class->getParentClass();
        }

        //Handle nodes
        if ($entity = $this->reader->getClassAnnotation($class, 'LRezek\\Neo4j\\Annotation\\Node'))
        {
			return $this->handleNode($entity, $class);
		}

        //Handle Relations
        elseif ($entity = $this->reader->getClassAnnotation($class, 'LRezek\\Neo4j\\Annotation\\Relation'))
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

	private function handleNode($node, $class)
	{
        $object = new Node($class->getName());

		$object->setRepositoryClass($node->repositoryClass);
        $object->loadProperties($this->reader, $class->getProperties());
        $object->validate();

        return $object;
    }

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

