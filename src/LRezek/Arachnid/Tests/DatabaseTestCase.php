<?php

namespace LRezek\Arachnid\Tests;

use LRezek\Arachnid\Arachnid;

abstract class DatabaseTestCase extends \PHPUnit_Framework_TestCase
{
    //Test id
    public $id;

    protected function getArachnid()
    {

        return new Arachnid(array(
             'transport' => 'curl', // or 'stream'
             'host' => 'localhost',
             'port' => 7474,
             'username' => 'neo',
             'password' => 'matrix',
             'proxy_dir' => '/tmp',
             'debug' => true, // Force proxy regeneration on each request
            // 'annotation_reader' => ... // Should be a cached instance of the doctrine annotation reader in production
        ));
    }

    protected static function printTime($function, $time)
    {
        static $header = false;
        static $test_num = 1;

        //Print the header
        if(!$header)
        {
            printf("-----------------------------------------------------------------------\n");
            printf("------------------------- \033[1mRUN TIME STATISTICS\033[0m -------------------------\n");
            printf("-----------------------------------------------------------------------\n");
            printf("| \033[1m%-8.8s\033[0m | \033[1m%-40.40s\033[0m | \033[1m%-12.12s\033[0m |\n", "TEST NUM", "TEST NAME", "RUN TIME");
            printf("-----------------------------------------------------------------------\n");
            $header = true;
        }

        if($time < 0.1)
        {
            printf("| %-8.8s | %-40.40s | %0.5f Secs |\n", $test_num++, $function, $time);
        }

        //Make it red, test is taking a long time
        else
        {
            printf("| %-8.8s | %-40.40s | \033[31m%0.5f Secs\033[0m |\n", $test_num++, $function, $time);
        }

    }
}

