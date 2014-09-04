## About

Neo4PHP is a PHP object graph mapper for Neo4J. It is built on top of [Josh Adell's Neo4J PHP Rest interface](https://github.com/jadell/neo4jphp).

This library is heavily based on the excellent work done by Louis-Philippe Huberdeau in his [PHP OGM](https://github.com/lphuberdeau/Neo4j-PHP-OGM).
The main difference is that this OGM allows you to define relationship objects as well as node objects, allowing you to attach properties to relationships very easily.

Released under the MIT Licence.

## Installation through Composer

To install the library through composer, you simply need to add the following to `composer.json` and run `composer update`:

```JavaScript
{
    "require": {
       "everyman/neo4jphp":"dev-master",
       "lrezek/neo4php":"dev-master"
    }
}
```
Once installed, you can use the Entity manager class (`LRezek/Neo4PHP/EntityManager`) as required.

## Documentation

For full documentation, as well as usage examples, please refer to the [wiki](https://github.com/lrezek/Neo4PHP/wiki/Table-of-Contents).
