<?php
/**
 * Contains the annotation class for the Property annotation. This specifies that the variable in the class is a property
 * in the graph element, and should be saved to the database as such.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Annotation;

/**
 * Annotation class for the <code>@OGM\Property</code> annotation.
 *
 * @package Arachnid
 * @subpackage Annotation
 *
 * @Annotation
 * @Target("PROPERTY")
 */
class Property
{
    /** @var string Allows you to set the property format in the annotation. */
    public $format = 'scalar';
}

