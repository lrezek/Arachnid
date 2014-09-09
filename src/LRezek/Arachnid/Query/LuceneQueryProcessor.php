<?php
/**
 * Contains the LuceneQueryProcessor class.
 *
 * @author Christophe Willemsen <willemsen.christophe@gmail.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Arachnid\Query;

/**
 * Class that processes requests through the Lucene Engine
 *
 * @package Arachnid
 * @subpackage Query
 */
class LuceneQueryProcessor
{
    const STRICT = 'STRICT_MODE';

    /** @var array List of parameters. */
    protected $_args;

    /** @var string Storage for the built query. */
    protected $query;

    /** @var array List of expanded expressions. */
    protected $allowedExpressions = array('AND' => ' AND ');

    /**
     * Initializes the query processor.
     */
    public function __construct()
    {
        foreach($this->allowedExpressions as $expr => $queryExpr) {
            $this->_args[$expr] = array();
        }
    }

    /**
     * Adds a query term to the query, currently only used for "AND" expressions.
     *
     * @param string $term The term to add.
     * @param string $value The value to add for the term.
     * @param string $expr The expression to use (default = AND).
     * @param string $mode What mode to use (default = STRICT).
     */
    public function addQueryTerm($term, $value, $expr = 'AND', $mode = self::STRICT)
    {
        if($this->isValidExpression($expr))
        {
            $method = '_'.strtolower($expr);
            $this->$method($term, $value);
        }
    }

    /**
     * Adds a 'AND' search to the query array.
     *
     * @param string $term The term to add.
     * @param string $value The value fo the term.
     */
    public function _and($term, $value)
    {
        $value = $this->prepareValue($value);
        array_push($this->_args['AND'], array('term' => $term, 'value' => $value));
    }

    /**
     * Embeds the value between "" if she contains spaces. If first character is "(" it adds no quotes.
     *
     * @param string $value The value to prepare.
     * @return string The prepared value.
     */
     public function prepareValue($value)
    {
        $value = trim($value);

        if(strpos($value, ' '))
        {
            $fl = mb_substr($value, 0, 1, 'UTF-8');

            if ($fl != '(')
                return '"'.$value.'"';
            else
                return $value;
        }
        return $value;
    }

    /**
     * Checks whether or not the given expression is valid.
     *
     * @param string $expr The expression to check.
     * @return bool Whether the expression is valid.
     * @throws \InvalidArgumentException thrown if the expression is invalid.
     */
    public function isValidExpression($expr)
    {
        if(!array_key_exists($expr, $this->allowedExpressions))
        {
            throw new \InvalidArgumentException(sprintf('The expression "%s" is not a valid expression', $expr));
        }
        return true;
    }

    /**
     * Returns an array with the valid expressions to use.
     *
     * @returns array The valid expressions.
     */
    public function getValidExpressions()
    {
        return $this->allowedExpressions;
    }

    /**
     * Returns the query to be sent to the Lucene Query Engine.
     *
     * @returns string The query.
     */
    public function getQuery()
    {
        $query = '';

        foreach($this->allowedExpressions as $expr => $queryExpr)
        {
            $arguments = $this->_args[$expr];
            $inlinedArguments = array();

            foreach($arguments as $arg)
            {
                $inlinedArguments[] = $arg['term'].':'.$arg['value'];
            }

            if('' !== $query)
            {
                $query .= $queryExpr;
            }

            $query .= implode($queryExpr, $inlinedArguments);
        }

        return $query;
    }
}

