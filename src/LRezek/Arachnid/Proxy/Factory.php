<?php
/**
 * Contains the Factory class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Proxy;

use LRezek\Arachnid\Exception;

/**
 * Proxy Factory class
 *
 * This class is responsible for making proxy objects, which are used to convert an Everyman node/relation back into an
 * entity.
 *
 * @package Arachnid
 * @subpackage Proxy
 */
class Factory
{
    /** @var string The directory to use for writing proxy classes.*/
    private $proxyDir;

    /** @var bool Debug flag. */
    private $debug;

    /**
     * Initializes the proxy factory.
     *
     * Initializes the proxy factory instance with a proxy directory and debug option.
     *
     * @param string $proxyDir The directory to use for classes.
     * @param bool $debug The debug flag.
     */
    function __construct($proxyDir = '/tmp', $debug = false)
    {
        $this->proxyDir = rtrim($proxyDir, '/');
        $this->debug = (bool) $debug;
    }

    /**
     * Creates a proxy object for an Everyman node.
     *
     * This creates a proxy object for a Everyman node, given the meta repository.
     *
     * @param \Everyman\Neo4j\Node $node The node to create a proxy of.
     * @param \LRezek\Arachnid\Meta\Repository $repository The meta repository.
     * @param string $class The class name.
     * @return mixed The proxy object.
     */
    function fromNode($node, $repository, $class)
    {
        return $this->fromEntity($node, $class, $repository);
    }

    /**
     * Creates a proxy object for an Everyman relationship.
     *
     * This creates a proxy object for a Everyman relationship, given the meta repository.
     *
     * @param \Everyman\Neo4j\Relationship $relationship The node to create a proxy of.
     * @param \LRezek\Arachnid\Meta\Repository $repository The meta repository.
     * @param callable $loadCallback Callback for start/end node lazy loading.
     * @return mixed The proxy object.
     */
    function fromRelation($relationship, $repository, \Closure $loadCallback)
    {
        //Get the class name from the node, and the meta from that class name
        $class = $relationship->getType();

        //Create the proxy factory
        return $this->fromEntity($relationship, $class, $repository, $loadCallback);
    }

    /**
     * Creates a proxy object for a Everyman node or relationship.
     *
     * This creates a proxy object for a Everyman relationship, given the meta repository and class name
     *
     * @param \Everyman\Neo4j\Node|\Everyman\Neo4j\Relationship $entity The entity to create a proxy of.
     * @param string $class The class name.
     * @param \LRezek\Arachnid\Meta\Repository $repository The meta repository.
     * @param null $callback The load callback to use for start/end nodes.
     * @return mixed The proxy object.
     */
    function fromEntity($entity, $class, $repository, $callback = null)
    {
        $meta = $repository->fromClass($class);

        //Create the proxy object and set the meta, node, and load callback
        $proxy = $this->createProxy($meta);
        $proxy->__setMeta($meta);
        $proxy->__setEntity($entity);
        $proxy->__setLoadCallback($callback);
        $proxy->__setEntity($entity);

        //Set the primary key property in the object to the node id.
        $pk = $meta->getPrimaryKey();
        $pk->setValue($proxy, $entity->getId());
        $proxy->__addHydrated($pk->getName());

        //Set the properties
        foreach ($meta->getProperties() as $property)
        {
            $name = $property->getName();

            //If the value isn't null in the DB, set the property to the correct value
            if($value = $entity->getProperty($name))
            {
                $property->setValue($proxy, $value);
                $proxy->__addHydrated($name);
            }
        }

        return $proxy;

    }

    /**
     * Creates a proxy class for a graph element.
     *
     * This method will create a proxy class for an entity that extends the required class and implements
     * LRezek\Arachnid\Proxy\Entity. This class will be generated and stored in the directory specified by the $proxyDir
     * property of this class. This is done so that the object returned by a query seemingly matches the object type
     * being queried for, while retaining its ID and other required information.
     *
     * @param \LRezek\Arachnid\Meta\GraphElement $meta The meta for the entity object being proxied.
     * @return mixed An instance of the proxy class.
     * @throws \LRezek\Arachnid\Exception If something goes wrong in file writing.
     */
    private function createProxy(\LRezek\Arachnid\Meta\GraphElement $meta)
    {
        //Get the proxy class name, as well as the regular class name
        $proxyClass = $meta->getProxyClass();
        $className = $meta->getName();

        //If the class already exists, just make an instance of it with the correct properties and return it.
        if(class_exists($proxyClass, false))
        {
            return $this->newInstance($proxyClass);
        }

        //Create a target file for the class
        $targetFile = "{$this->proxyDir}/$proxyClass.php";

        //If the file doesn't exist
        if($this->debug || !file_exists($targetFile))
        {
            //Initialize functions
            $functions = '';

            $reflectionClass = new \ReflectionClass($className);

            //Loop through entities public methods
            foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
            {
                //If the method isn't a constructor, destructor, or final, add it to the methods via method proxy.
                if(!$method->isConstructor() && !$method->isDestructor() && !$method->isFinal())
                {
                    $functions .= $this->methodProxy($method, $meta);
                }
            }

            //Get the properties and primary key.
            $properties = $meta->getProperties();
            $properties[] = $meta->getPrimaryKey();

            //Filter out private properties
            $properties = array_filter($properties, function ($property) {
                return !$property->isPrivate();
            });

            //Create an array map by property name
            $properties = array_map(function ($property) {
                return $property->getName();
            }, $properties);

            //Create php code for properties.
            $properties = var_export($properties, true);

            //Create the actual class.
            $content = <<<CONTENT
<?php

class $proxyClass extends $className implements LRezek\\Arachnid\\Proxy\\Entity
{
    private \$neo4j_hydrated = array();
    private \$neo4j_meta;
    private \$neo4j_entity;
    private \$neo4j_loadCallback;

    function getEntity()
    {
        \$entity = new $className;

        foreach (\$this->neo4j_meta->getProperties() as \$prop)
        {
            \$prop->setValue(\$entity, \$prop->getValue(\$this));
        }

        \$prop = \$this->neo4j_meta->getPrimaryKey();
        \$prop->setValue(\$entity, \$prop->getValue(\$this));

        return \$entity;
    }

    $functions

    function __addHydrated(\$name)
    {
        \$this->neo4j_hydrated[] = \$name;
    }

    function __setMeta(\$meta)
    {
        \$this->neo4j_meta = \$meta;
    }

    function __setEntity(\$entity)
    {
        \$this->neo4j_entity = \$entity;
    }

    function __getEntity()
    {
        return \$this->neo4j_entity;
    }

    function __setLoadCallback(\$loadCallback)
    {
        \$this->neo4j_loadCallback = \$loadCallback;
    }

    private function __load(\$name, \$propertyName)
    {
        //Already hydrated
        if(in_array(\$propertyName, \$this->neo4j_hydrated))
        {
            return;
        }

        if(! \$this->neo4j_meta)
        {
            throw new \\LRezek\\Arachnid\\Exception('Proxy not fully initialized.');
        }

        \$property = \$this->neo4j_meta->findProperty(\$name);

        if(strpos(\$name, 'set') === 0)
        {
            \$this->__addHydrated(\$propertyName);
            return;
        }

        //Make property node
        if(\$property->isStart() || \$property->isEnd())
        {
            \$loader = \$this->neo4j_loadCallback;

            //Get the node
            if(\$property->isStart())
            {
                \$node = \$this->neo4j_entity->getStartNode();
            }
            else
            {
                \$node = \$this->neo4j_entity->getEndNode();
            }

            \$node = \$loader(\$node);

            //Set the property
            \$property->setValue(\$this, \$node);
        }

        //Hydrate the property
        \$this->__addHydrated(\$propertyName);

    }

    function __sleep()
    {
        return $properties;
    }
}


CONTENT;

            //Make sure the proxy directory is an actual directory
            if(!is_dir($this->proxyDir))
            {
                if(false === @mkdir($this->proxyDir, 0775, true))
                {
                    throw new Exception('Proxy Dir is not writable.');
                }
            }

            //Make sure the directory is writable
            else if(!is_writable($this->proxyDir))
            {
                throw new Exception('Proxy Dir is not writable.');
            }

            //Write the file
            file_put_contents($targetFile, $content);
        }

        //Add file to class loader
        require $targetFile;

        //Return an instance of the proxy class
        return $this->newInstance($proxyClass);
    }

    /**
     * Create a new instance of a proxy class object.
     *
     * This creates an instance of a proxy class. If the class has already been created, it is retrieved from a static
     * prototypes array.
     *
     * @param mixed $proxyClass The class to create an instance of.
     * @return mixed The new instance of <code>$proxyclass</code>.
     */
    private function newInstance($proxyClass)
    {
        static $prototypes = array();

        if(!array_key_exists($proxyClass, $prototypes))
        {
            $prototypes[$proxyClass] = unserialize(sprintf('O:%d:"%s":0:{}', strlen($proxyClass), $proxyClass));
        }

        return clone $prototypes[$proxyClass];
    }


    /**
     * Creates proxy methods from the reflection method handle and meta information.
     *
     * @param $method
     * @param $meta
     * @return string The method code.
     */
    private function methodProxy($method, $meta)
    {
        //Find out if the method is a property getter or setter
        $property = $meta->findProperty($method->getName());

        //If it is not, don't need the proxy
        if(!$property)
        {
            return;
        }

        //If the method is a straight up property, you don't need a method proxy either.
        if($property->isProperty() && !$property->isStart() && !$property->isEnd())
        {
            return;
        }

        $parts = array();
        $arguments = array();

        //Loop through the methods parameters
        foreach ($method->getParameters() as $parameter)
        {
            //Convert to a variable name, and add it to parts array
            $variable = '$' . $parameter->getName();
            $parts[] = $variable;

            $arg = $variable;

            //If the parameter is optional, set it to its default value in the arguments list.
            if($parameter->isOptional())
            {
                $arg .= ' = ' . var_export($parameter->getDefaultValue(), true);
            }

            //If the variable is passed by reference, put in an ampersand
            if($parameter->isPassedByReference())
            {
                $arg = "& $arg";
            }

            //If the variable is a set class, add the class name as the type
            elseif($c = $parameter->getClass())
            {
                $arg = $c->getName() . ' ' . $arg;
            }

            //If the argument is a array, add array identifier
            if($parameter->isArray())
            {
                $arg = "array $arg";
            }

            //Add the argument to argument array
            $arguments[] = $arg;
        }

        //Join the argument variable names together with commas
        $parts = implode(', ', $parts);

        //Join the arguments with commas
        $arguments = implode(', ', $arguments);

        //Get the method name as a string
        $name = var_export($method->getName(), true);

        //Get the property name as a string
        $propertyName = var_export($property->getName(), true);

        return <<<FUNC

    function {$method->getName()}($arguments)
    {
        self::__load($name, $propertyName);
        return parent::{$method->getName()}($parts);
    }

FUNC;
    }
}

