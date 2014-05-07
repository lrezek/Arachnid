<?php
/**
 * Contains the Reflection meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */
namespace LRezek\Neo4j\Meta;

class Reflection
{
    public static function getProperty($methodName)
    {
        $property = substr($methodName, 3);
        return self::singularizeProperty($property);
    }

    public static function singularizeProperty($property)
    {
        $property = lcfirst($property);

//        //Deal with famil-'ies', make it 'family'
//        if ('ies' == substr($property, -3)) {
//            $property = substr($property, 0, -3) . 'y';
//        }
//
//        //Deal with 'friends', make it 'friend'
//        if (preg_match('/[^s]s$/', $property))
//        {
//            $property = substr($property, 0, -1);
//        }

        return $property;
    }
}

