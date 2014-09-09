<?php
/**
 * Contains the Reflection meta class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */
namespace LRezek\Arachnid\Meta;

/**
 * Deals with converting english property names to real ones through singularization.
 *
 * @package Arachnid
 * @subpackage Meta
 */
class Reflection
{
    /**
     * Gets the property relating to a method name for searching.
     *
     * @param string $methodName Name of the method called.
     * @return string Property name.
     */
    public static function getProperty($methodName)
    {
        $property = substr($methodName, 3);
        return self::singularizeProperty($property);
    }

    /**
     * Singularizes a property name, by making it lowercase and stripping off "ies" or "s"
     *
     * @param string $property Property name to sigularize.
     * @return string Singularized property name.
     */
    public static function singularizeProperty($property)
    {
        //Remove an underscore, so you can do get_date()
        if(substr($property,0,1) == '_')
        {
            $property = substr($property, 1);
        }

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

