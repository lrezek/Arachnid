<?php

namespace LRezek\Neo4j\Query;

/**
 * Class that process requests through the Lucene Engine
 * 
 * @author Christophe Willemsen <willemsen.christophe@gmail.com>
 */
class LuceneQueryProcessor
{
    const STRICT = 'STRICT_MODE';

    protected $_args;
    protected $query;
    protected $allowedExpressions = array('AND' => ' AND ');

    public function __construct()
    {
        foreach($this->allowedExpressions as $expr => $queryExpr) {
            $this->_args[$expr] = array();
        }
    }

    public function addQueryTerm($term, $value, $expr = 'AND', $mode = self::STRICT)
    {
        if($this->isValidExpression($expr)) {
            $method = '_'.strtolower($expr);
            $this->$method($term, $value);
        }
    }

    /**
     * Adds a 'AND' search to the query array
     */
    public function _and($term, $value)
    {
        $value = $this->prepareValue($value);
        array_push($this->_args['AND'], array('term' => $term, 'value' => $value));
    }

    /**
     * Embed the value between "" if she contains spaces
     * If first character is "(" it adds no quotes
     */
     public function prepareValue($value)
    {
        $value = trim($value);
        if(strpos($value, ' ')) {
            $fl = mb_substr($value, 0, 1, 'UTF-8');
            if ($fl != '(')
                return '"'.$value.'"';
            else
                return $value;
        }
        return $value;
    }

    /**
     * Checks whether or not the given expression is valid
     */
    public function isValidExpression($expr)
    {
        if(!array_key_exists($expr, $this->allowedExpressions)) {
            throw new \InvalidArgumentException(sprintf('The expression "%s" is not a valid expression', $expr));
        }
        return true;
    }

    /**
     * Returns an array with the valid expressions to use
     */
    public function getValidExpressions()
    {
        return $this->allowedExpressions;
    }

    /**
     * Returns the query to be send to the Lucene Query Engine
     */
    public function getQuery()
    {
        $query = '';

        foreach($this->allowedExpressions as $expr => $queryExpr) {
            $arguments = $this->_args[$expr];
            $inlinedArguments = array();
            foreach($arguments as $arg) {
                $inlinedArguments[] = $arg['term'].':'.$arg['value'];
            }
            if('' !== $query) {
                $query .= $queryExpr;
            }
            $query .= implode($queryExpr, $inlinedArguments);
        }

        return $query;
    }
}

