<?php
/**
 * Contains the ParameterProcessor class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Query;

/**
 * Class that processes query parameters.
 *
 * @package Arachnid
 * @subpackage Query
 */
class ParameterProcessor
{
    const CYPHER = 'cypher';

    /** @var string The mode to use (default = "cypher"). */
    private $mode;

    /** @var  string The query string. */
    private $query;

    /** @var array Query parameters. */
    private $parameters = array();

    /**
     * Initializes the parameter processor.
     *
     * @param string $mode The mode to use (default = "cypher")
     */
    function __construct($mode = 'cypher')
    {
        $this->mode = $mode;
    }

    /**
     * Sets the query string.
     *
     * @param string $string The query string.
     */
    function setQuery($string)
    {
        $this->query = $string;
    }

    /**
     * Gets the query string.
     *
     * @return string The query string.
     */
    function getQuery()
    {
        return $this->query;
    }

    /**
     * Sets a query parameter.
     *
     * @param string $name The name of the parameter.
     * @param mixed $value The value of the parameter.
     */
    function setParameter($name, $value)
    {
        if (is_object($value) && method_exists($value, 'getId'))
        {
            $this->parameters[$name] = $value->getId();
        }

        else
        {
            $this->parameters[$name] = $value;
        }
    }

    /**
     * Processes parameters and returns an array of them afterwards.
     *
     * @return array The processed parameters.
     */
    function process()
    {
        $mode = $this->mode;
        $parameters = $this->parameters;
        $string = $this->query;

        $string = str_replace('[:', '[;;', $string);

        $parameters = array_filter($parameters, function ($value) use (& $parameters, & $string, $mode)
        {
            $key = key($parameters);
            next($parameters);

            if (is_numeric($value))
            {
                $string = str_replace(":$key", $value, $string);
                return false;
            }

            else
            {
                if ($mode == 'cypher')
                {
                    $string = str_replace(":$key", '{' . $key . '}', $string);
                }

                else
                {
                    $string = str_replace(":$key", $key, $string);
                }

                return true;
            }
        });

        $string = str_replace('[;;', '[:', $string);

        $this->query = $string;
        return $parameters;
    }
}

