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
     * Finds meta for a property by name.
     *
     * @param string $name The name of the property.
     * @return \LRezek\Arachnid\Meta\Property|null The property, if it is found.
     */
    function findProperty($name)
    {
        return parent::findProperty($name);
	}
}
