<?php
/**
 * Contains the Repository class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP;
use Doctrine\Common\Collections\ArrayCollection;
use Everyman\Neo4j\Relationship;
use LRezek\Neo4PHP\Meta\Node;
use LRezek\Neo4PHP\Meta\Relation;
use LRezek\Neo4PHP\Query\LuceneQueryProcessor;

/**
 * Contains base code for repositories.
 *
 * All queries are done through repository classes. These are stored in the entity manager, and can be accessed with
 * $em->getRepository($className)
 *
 * @package Neo4j
 */
class Repository
{
    /** @var \LRezek\Neo4PHP\Meta\GraphElement The meta information for the graph element. */
    private $meta;

    /** @var \Everyman\Neo4j\Index\NodeIndex|\Everyman\Neo4j\Index\RelationshipIndex The index info for the class. */
    private $index;

    /** @var \LRezek\Neo4PHP\EntityManager The entity manager. */
    private $entityManager;

    /** @var string The name of the class. */
    private $class;

    /**
     * Initializes the repository instance.
     *
     * Initializes the repository instance based on a classes meta information.
     *
     * @param EntityManager $entityManager The entity manager.
     * @param Meta\GraphElement $meta The classes meta information.
     */
    function __construct(EntityManager $entityManager, Meta\GraphElement $meta)
    {
        $this->entityManager = $entityManager;
        $this->class = $meta->getName();
        $this->meta = $meta;
    }

    /**
     * Gets the index object for the class this repository applies to.
     *
     * Gets the Everyman index object for the class. If it hasn't been initialized, this will be done before the
     * object is returned.
     *
     * @return \Everyman\Neo4j\Index\NodeIndex|\Everyman\Neo4j\Index\RelationshipIndex The index object.
     */
    function getIndex()
    {

        //If there isn't an index, make one and save it
        if (! $this->index)
        {
            $this->index = $this->entityManager->createIndex($this->class);
            $this->index->save();
        }

        return $this->index;
    }

    /**
     * Saves the index information for this class to the database.
     *
     * This saves the classes Everyman index object to the database, if it exists. If it does not, do nothing.
     */
    function writeIndex()
    {
        if ($this->index)
        {
            $this->index->save();
        }
    }


    /**
     * Gets all nodes or relations.
     *
     * @return ArrayCollection All nodes or relations.
     * @api
     */
    function findAll()
    {
        $collection = new ArrayCollection();

        if($this->meta instanceof Node)
        {
            foreach($this->getIndex()->query('id:*') as $node)
            {
                $collection->add($this->entityManager->loadNode($node));
            }
        }
        elseif($this->meta instanceof Relation)
        {
            foreach($this->getIndex()->query('id:*') as $rel)
            {
                $collection->add($this->entityManager->loadRelation($rel));
            }
        }

        return $collection;
    }

    /**
     * Creates a Cypher query.
     *
     * @return Query\Cypher
     */
    protected function createCypherQuery()
    {
        return $this->entityManager->createCypherQuery();
    }


    /**
     * Finds one node or relation by search criteria.
     *
     * @param array $criteria The search criteria.
     * @throws Exception If no criterion supplied.
     * @return mixed|null The node/Relation or Null if not found.
     * @api
     */
    public function findOneBy(array $criteria)
    {

        //If this repository is for a node
        if($this->meta instanceof Node)
        {
            $query = $this->createQuery($criteria);

            if ($node = $this->getIndex()->queryOne($query))
            {
                return $this->entityManager->loadNode($node);
            }
        }

        //Relation
        elseif($this->meta instanceof Relation)
        {
            //Array of different results
            $result_sets = array();

            //Criteria to use for query
            $query_criteria = array();

            foreach($criteria as $k => $v)
            {
                $key = Meta\Reflection::singularizeProperty($k);

                //If it's the start or end
                if($key == $this->meta->getStart()->getName() || $key == $this->meta->getEnd()->getName())
                {
                   //Load relation by node (null if the property isn't a start/end)
                   $result_sets[] = $this->getRelationsByNode($key, $v);
                }

                //Regular property, add to query criteria to run later
                else
                {
                   $query_criteria[$k] = $v;
                }
            }

            //Do the query
            if(count($query_criteria) > 0)
            {
                $query = $this->createQuery($query_criteria);
                $result_sets[] = $this->getIndex()->query($query);
            }

            if(count($result_sets) == 0)
            {
                throw new Exception("Please supply at least one criteria to findOneBy()");
            }

            //Intersect the 3 potential result sets (if they are there)
            $results = count($result_sets) > 1 ? call_user_func_array('array_intersect', $result_sets) : $result_sets[0];

            //No results
            return empty($results) ? null : $this->entityManager->loadRelation($results[0]);

        }

        return null;
    }

    /**
     * Finds all nodes an relations by search criteria.
     *
     * @param array $criteria The search criteria.
     * @throws Exception If no criterion supplied.
     * @return ArrayCollection Collection of nodes or relations.
     * @api
     */
    public function findBy(array $criteria)
    {

        $collection = new ArrayCollection();

        //If this repository is for a node
        if($this->meta instanceof Node)
        {
            $query = $this->createQuery($criteria);

            foreach($this->getIndex()->query($query) as $node)
            {
                $collection->add($this->entityManager->loadNode($node));
            }
        }

        //Is a relation
        elseif($this->meta instanceof Relation)
        {
            //Array of different results
            $result_sets = array();

            //Criteria to use for query
            $query_criteria = array();

            foreach($criteria as $k => $v)
            {
                $key = Meta\Reflection::singularizeProperty($k);

                //If it's the start or end
                if($key == $this->meta->getStart()->getName() || $key == $this->meta->getEnd()->getName())
                {
                    //Load relation by node (null if the property isn't a start/end)
                    $result_sets[] = $this->getRelationsByNode($key, $v);
                }

                //Regular property, add to query criteria to run later
                else
                {
                    $query_criteria[$k] = $v;
                }
            }

            //Do the query
            if(count($query_criteria) > 0)
            {
                $query = $this->createQuery($query_criteria);
                $result_sets[] = $this->getIndex()->query($query);
            }

            if(count($result_sets) == 0)
            {
                throw new Exception("Please supply at least one criteria to findBy()");
            }

            //Intersect the 3 potential result sets (if they are there)
            $results = count($result_sets) > 1 ? call_user_func_array('array_intersect', $result_sets) : $result_sets[0];

            foreach($results as $rel)
            {
                $collection->add($this->entityManager->loadRelation($rel));
            }
        }

        return $collection;
    }

    /**
     * Calls the Lucene Query Processor to build the query.
     *
     * @param array $criteria An array of search criterion.
     * @throws \InvalidArgumentException If there are no search criterion.
     * @return string The query string.
     * @api
     */
    public function createQuery(array $criteria = array())
    {
        if(!empty($criteria))
        {
            $queryProcessor = new LuceneQueryProcessor();

            foreach($criteria as $key => $value) {
                $queryProcessor->addQueryTerm($key, $value);
            }
            return $queryProcessor->getQuery();
        }
        throw new \InvalidArgumentException('The criteria passed to the find** method can not be empty');
    }

    /**
     * Gets called anytime a function is called, and redirects calls to "FindOneByProperty" or "FindByProperty"
     * to a search by the property.
     *
     * @param string $name Method name.
     * @param string $arguments Method arguments.
     * @return ArrayCollection|mixed
     */
    function __call($name, $arguments)
    {
        //If the call starts with 'findOneBy'
        if (strpos($name, 'findOneBy') === 0)
        {

            //If this repository is for a node
            if($this->meta instanceof Node)
            {
                //Get the property
                $property = $this->getSearchableProperty(substr($name, 9));

                //Search index for node
                if ($node = $this->getIndex()->findOne($property, $arguments[0]))
                {
                    //Return the loaded node object
                    return $this->entityManager->loadNode($node);
                }

            }

            //Repository is for a relation
            else
            {
                //Singularize the property
                $prop = Meta\Reflection::singularizeProperty(substr($name, 9));

                //If it's the start or end
                if($prop == $this->meta->getStart()->getName() || $prop == $this->meta->getEnd()->getName())
                {
                    //Load relation by node (null if the property isn't a start/end)
                    $rels = $this->getRelationsByNode($prop, $arguments[0]);

                    //Return a clean null if there are no results
                    return empty($rels) ? null : $this->entityManager->loadRelation($rels[0]);
                }

                //Not a start or end node
               else
               {
                    //Check if it's indexed
                    $property = $this->getSearchableProperty(substr($name, 9));

                    //Find the relation
                    if ($relation = $this->getIndex()->findOne($property, $arguments[0]))
                    {
                        //Return the loaded relation object
                        return $this->entityManager->loadRelation($relation);
                    }
                }
            }

        }

        //If the call starts with findBy
        elseif (strpos($name, 'findBy') === 0)
        {

            //If this repository is for a node
            if($this->meta instanceof Node)
            {
                //Get the property
                $property = $this->getSearchableProperty(substr($name, 6));

                $collection = new ArrayCollection;

                foreach ($this->getIndex()->find($property, $arguments[0]) as $node)
                {
                    $collection->add($this->entityManager->loadNode($node));
                }

                return $collection;
            }

            //Repository for a relation
            else
            {
                //Singularize the property
                $prop = Meta\Reflection::singularizeProperty(substr($name, 6));

                //If it's the start or end
                if($prop == $this->meta->getStart()->getName() || $prop == $this->meta->getEnd()->getName())
                {
                    //Load relations by node (null if the property isn't a start/end)
                    $rels = $this->getRelationsByNode($prop, $arguments[0]);

                    $collection = new ArrayCollection;

                    foreach ($rels as $rel)
                    {
                        $collection->add($this->entityManager->loadRelation($rel));
                    }

                    return $collection;
                }

                //The property wasn't a start/end
                else
                {
                    //Check if it's indexed
                    $property = $this->getSearchableProperty(substr($name, 6));

                    $collection = new ArrayCollection;

                    foreach ($this->getIndex()->find($property, $arguments[0]) as $relation)
                    {
                        $collection->add($this->entityManager->loadRelation($relation));
                    }

                    return $collection;
                }
            }
        }

        
    }

    /**
     * Gets a indexed property name, after doing required string manipulations.
     *
     * @param string $property Property name.
     * @return string The searchable index name.
     * @throws Exception Thrown if the property is not indexed.
     */
    private function getSearchableProperty($property)
    {
        $property = Meta\Reflection::singularizeProperty($property);

        foreach ($this->meta->getIndexedProperties() as $p)
        {
            if (Meta\Reflection::singularizeProperty($p->getName()) == $property)
            {
                return $property;
            }
        }

        //Node properties only have indexing problems
        if($this->meta instanceof Node)
        {
            throw new Exception("Property $property is not indexed.");
        }

        //Relations have start/end or indexing problems
        else
        {
            throw new Exception("Property $property is not indexed or the start/end of a relationship");
        }
    }

    /**
     * Retrieves relations by start/end node property
     *
     * @param $prop
     * @param mixed $node The argument supplied.
     * @throws Exception If called on a node repository.
     * @throws \InvalidArgumentException If anything other than a node is supplied and searched for, if the node is not saved, or if the property is not a start/end property.
     * @internal param string $property The property name (singularized).
     * @return null|array Array of relationship objects, or null if property isn't a start/end node.
     */
    private function getRelationsByNode($prop, $node)
    {
        if(! ($this->meta instanceof Relation))
        {
            throw new Exception('Cannot call findByStartNode on a node repository.');
        }

        //Get meta info for the node
        $entity_meta = $this->entityManager->getMetaRepository()->fromClass(get_class($node));

        if(! $entity_meta instanceof Node)
        {
            throw new \InvalidArgumentException("You must supply a node to search for relations by node.");
        }

        //Get node primary key
        $id = $entity_meta->getPrimaryKey()->getValue($node);

        if(! $id)
        {
            throw new \InvalidArgumentException('Node must be saved to find its relations.');
        }

        //Grab the everyman node
        $enode = $this->entityManager->getClient()->getNode($id);

        //Looking for start
        if($prop == $this->meta->getStart()->getName())
        {
            $dir = Relationship::DirectionOut;
        }

        //Looking for end
        elseif($prop == $this->meta->getEnd()->getName())
        {
            $dir = Relationship::DirectionIn;
        }

        else
        {
            throw new \InvalidArgumentException("Property $prop is not a start/end node property.");
        }

        //Grab the nodes relations (of this type)
        $rels = $enode->getRelationships($this->meta->getName(), $dir);

        return $rels;

    }
}
