<?php
/**
 * Contains the Node meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Meta;

use \LRezek\Arachnid\Exception;

/**
 * Defines meta for a Node.
 *
 * This is a meta class that defines the meta for a Node. It is a meta object that extends \LRezek\Arachnid\Meta\GraphElement,
 * meaning it only contains property information for a node, retrieved through an annotation reader. It does NOT contain
 * actual property values, just a list of indexed/non-indexed properties and the primary key for a class that has the
 * correct node annotations. It also contains the repository class to use when querying for objects of this type.
 *
 * @package Arachnid
 * @subpackage Meta
 */
class Node extends GraphElement
{
    /** @var string The repository class to use for queries.*/
    private $repositoryClass = 'LRezek\\Arachnid\\Repository';

    /**
     * Sets the repository class for nodes.
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
     * Retrieves the repository class for nodes.
     *
     * @return string The name of the repository class.
     */
    function getRepositoryClass()
    {
        return $this->repositoryClass;
    }

    /**
     * Loads the required properties.
     *
     * Loops through properties and saves them based on their annotation, using the annotation reader supplied.
     * Properties are saved in <code>$this->properties</code>, indexed properties are saved in <code>$this->indexedProperties</code>, and the
     * primary key is saved in <code>$this->primaryKey</code>.
     *
     * @param \Doctrine\Common\Annotations\AnnotationReader $reader The annotation reader to use.
     * @param \ReflectionProperty[] $properties Array of reflection properties, from <code>reflectionClass->getProperties()</code>.
     * @throws Exception If the node contains a start or end property.
     */
    public function loadProperties($reader, $properties)
    {
        //Loop through properties
        foreach ($properties as $property)
        {
            $prop = new Property($reader, $property);

            //A node can't have a start.
            if($prop->isStart())
            {
                throw new Exception("A node entity cannot contain a start property (@Start).");
            }

            //A node can't have a end.
            else if($prop->isEnd())
            {
                throw new Exception("A node entity cannot contain an end property (@End).");
            }

            //Load the property (auto, property, indexed)
            $this->loadProperty($prop);
        }
    }
}
