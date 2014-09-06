<?php
/**
 * Contains the annotation class for the Relation annotation. This specifies that the class is a relation object, and thus
 * requires all the relation annotations (Start, End, and Auto).
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP\Annotation;

/**
 * Annotation class for the <code>@OGM\Relation</code> annotation.
 *
 * @package Neo4PHP
 * @subpackage Annotation
 *
 * @Annotation
 * @Target("CLASS")
 */
class Relation
{
    /** @var string Allows you to set the repository class in the class annotation. */
    public $repositoryClass;
}

