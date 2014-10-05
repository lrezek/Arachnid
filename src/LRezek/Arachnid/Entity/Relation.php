<?php

/**
 * Contains the relation class. Extending this class will enable arachnid functionality in the same way as the supplied
 * annotations, but with a more advanced query builder, and lots of reflections.
 */
namespace LRezek\Arachnid\Entity;

class Relation extends Entity
{
    protected $start = 'from';
    protected $end = 'to';
}

?> 