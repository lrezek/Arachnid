<?php
/**
 * Contains the Configuration class for the database connection.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid;

use Everyman\Neo4j\Client;
use Everyman\Neo4j\Transport;

/**
 * Configuration class for the entity manager.
 *
 * Contains all entity manager settings, including the transport type, host ip, port, username/password, and proxy
 * directory.
 *
 * @package Arachnid
 */
class Configuration
{
    /** @var string Database transport type, default is "default". */
    private $transport = 'default';

    /** @var string Database host address, default is "localhost". */
    private $host = 'localhost';

    /** @var int Database port, default is 7474. */
    private $port = 7474;

    /** @var string Directory to write proxy classes. Default is "/tmp". */
    private $proxyDir = '/tmp';

    /** @var bool Debug flag, default is false. */
    private $debug = false;

    /** @var \Doctrine\Common\Annotations\AnnotationReader Annotation reader to use for meta information. */
    private $annotationReader;

    /** @var string Username to use for accessing neo4j DB. Default is null. */
    private $username;

    /** @var string Password to use for accessing neo4j DB. Default is null. */
    private $password;

    /** @var callable Date generation function to use. */
    private $dateGenerator;

    /**
     * Constructor method.
     *
     * Constructs the configuration object with the supplied configuration array.
     *
     * @param array $configs The configuration to use.
     */
    function __construct(array $configs = array())
    {
        if(isset($configs['transport']))
        {
            $this->transport = $configs['transport'];
        }

        if(isset($configs['host']))
        {
            $this->host = $configs['host'];
        }

        if(isset($configs['port']))
        {
            $this->port = (int) $configs['port'];
        }

        if(isset($configs['debug']))
        {
            $this->debug = (bool) $configs['debug'];
        }

        if(isset($configs['proxy_dir']))
        {
            $this->proxyDir = $configs['proxy_dir'];
        }

        if(isset($configs['annotation_reader']))
        {
            $this->annotationReader = $configs['annotation_reader'];
        }

        if(isset($configs['username'], $configs['password']))
        {
            $this->username = $configs['username'];
            $this->password = $configs['password'];
        }

        //Set the date generator if required, or go default
        if(isset($configs['date_generator']))
        {
            $this->dateGenerator = $configs['date_generator'];
        }
        else
        {
            //Create a default generator
            $this->dateGenerator = function ()
            {
                $currentDate = new \DateTime;
                return $currentDate->format('Y-m-d H:i:s');
            };
        }
    }

    /**
     * Creates a everyman client based on the configuration parameters.
     *
     * @return Client
     */
    function getClient()
    {
        $transport = $this->getTransport();
        $transport->setAuth($this->username, $this->password);

        return new Client($transport);
    }

    /**
     * Gets the transport method.
     *
     * @return Transport\Curl|Transport\Stream
     */
    private function getTransport()
    {
        switch ($this->transport)
        {
            case 'stream':
                return new Transport\Stream($this->host, $this->port);

            case 'curl':
            default:
                return new Transport\Curl($this->host, $this->port);
        }
    }

    /**
     * Gets the proxy factory.
     *
     * @return Proxy\Factory
     */
    function getProxyFactory()
    {
        return new Proxy\Factory($this->proxyDir, $this->debug);
    }

    /**
     * Gets the meta repository.
     *
     * @return Meta\Repository
     */
    function getMetaRepository()
    {
        return new Meta\Repository($this->annotationReader);
    }

    /**
     * Gets the date generator function to use.
     *
     * @return callable The date generator to use.
     */
    function getDateGenerator()
    {
        return $this->dateGenerator;
    }
}

