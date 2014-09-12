# Arachnid [![Build Status](https://travis-ci.org/lrezek/Arachnid.svg?branch=master)](https://travis-ci.org/lrezek/Arachnid) [![Coverage Status](https://img.shields.io/coveralls/lrezek/Arachnid.svg)](https://coveralls.io/r/lrezek/Arachnid?branch=master) [![Latest Stable Version](https://poser.pugx.org/lrezek/arachnid/v/stable.svg)](https://packagist.org/packages/lrezek/arachnid) [![Latest Unstable Version](https://poser.pugx.org/lrezek/arachnid/v/unstable.svg)](https://packagist.org/packages/lrezek/arachnid) [![License](https://poser.pugx.org/lrezek/arachnid/license.svg)](https://packagist.org/packages/lrezek/arachnid)

Arachnid is a PHP object graph mapper for Neo4J. Spiders manage webs, Arachnid manages Neo4J Graphs.

This library is heavily based on the excellent work done by Louis-Philippe Huberdeau in his [PHP OGM](https://github.com/lphuberdeau/Neo4j-PHP-OGM).
The main difference is that this OGM allows you to define relationship objects as well as node objects, allowing you to attach properties to relationships very easily.

Arachnid is built on top of [Josh Adell's Neo4J PHP Rest interface](https://github.com/jadell/neo4jphp).

Released under the MIT Licence.

## Installation through Composer

To install the library through composer, you simply need to add the following to `composer.json` and run `composer update`:

```JavaScript
{
    "require": {
       "everyman/neo4jphp":"dev-master",
       "lrezek/arachnid":"dev-master"
    }
}
```
Once installed, you can use the Arachnid class (`LRezek/Arachnid/Arachnid`) as required.

## Documentation and Testing

You can generate PHP Documentor docs by running `vendor/bin/phpunit` while in the root folder of this repository.

You can run unit tests on the library by running `vendor/bin/phpdoc` while in the root folder of this repository.

For full documentation, as well as usage examples, please refer to the [wiki](https://github.com/lrezek/Arachnid/wiki/Table-of-Contents).
