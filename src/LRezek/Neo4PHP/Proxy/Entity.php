<?php
/**
 * Contains the Base Entity proxy interface.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP\Proxy;

/**
 * Base proxy entity interface.
 *
 * @package Neo4PHP
 * @subpackage Proxy
 */
interface Entity
{
    /**
     * Gets the original entity.
     * @return mixed The entity.
     */
    function getEntity();

    /**
     * Adds a property to the hydrated property list.
     * @param mixed $name Property to hydrate
     */
    function __addHydrated($name);

    /**
     * Attaches meta to the entity.
     * @param mixed $meta Meta to use for the entity.
     */
    function __setMeta($meta);

    /**
     * Set the entity this proxy class relates to.
     * @param mixed $node Entity to use.
     */
    function __setEntity($node);

    /**
     * Gets the attached entity.
     * @return mixed
     */
    function __getEntity();
}

