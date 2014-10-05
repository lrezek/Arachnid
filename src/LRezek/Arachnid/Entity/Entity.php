<?php

/**
 * Contains the entity parent class. This class provides common functionality between nodes and relations.
 */
namespace LRezek\Arachnid\Entity;

abstract class Entity
{
    protected $primary = 'id';
    protected $properties = array();
    protected $index = array();
}

?> 