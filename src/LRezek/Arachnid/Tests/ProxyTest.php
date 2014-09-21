<?php

use LRezek\Arachnid\Meta\Repository as MetaRepository;
use LRezek\Arachnid\Proxy\Factory as ProxyFactory;

/**
 * Created By: lrezek
 * Date: 09,13,2014
 */ 
class ProxyTest extends \PHPUnit_Framework_TestCase
{

    function testUnwritableDirectory()
    {
        $repo = new MetaRepository();
        $meta = $repo->fromClass('LRezek\\Arachnid\\Tests\\Entity\\User');

        $factory = new ProxyFactory("/asd");

//        $proxy = $factory->fromNode();
//
        //TODO: Work out this test, as well as making proxies of all different types of classes
    }

}

?> 