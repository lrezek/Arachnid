<?php
/**
 * Contains the Base Entity proxy interface.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4j\Proxy;

/**
 * Base proxy entity interface.
 *
 * @package Neo4j\Proxy
 */
interface Entity
{
    function getEntity();

    function __addHydrated($name);

    function __setMeta($meta);

    function __setEntity($node);

    function __getEntity();
}

