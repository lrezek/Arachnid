<?php
/**
 * Contains the EntityManager class.
 *
 * @author Lukas Rezek <lukas@miratronix.com>
 * @license MIT
 * @filesource
 * @version GIT: $Id$
 */

namespace LRezek\Neo4PHP;

use Everyman\Neo4j\Cypher\Query as InternalCypherQuery;
use Everyman\Neo4j\Index\NodeIndex;
use Everyman\Neo4j\Index\RelationshipIndex;
use Everyman\Neo4j\Node;
use LRezek\Neo4PHP\Meta\Relation;

/**
 * Handles communication with the database server, and keeps track of the various entities in the system.
 *
 * The entity manager is the class that controls everything in this library. It deals with all the required database calls,
 * as well as entity management. In order to use this library, you must create an instance of this class with the correct
 * configuration and use the available methods for database access.
 *
 * @package Neo4PHP
 */
class EntityManager
{
    const NODE_CREATE = "node.create";
    const RELATION_CREATE = 'relation.create';
    const QUERY_RUN = 'query.run';

    /** @var \Everyman\Neo4j\Client The actual Everyman client. */
    private $client;

    /** @var Proxy\Factory Proxy factory to use. */
    private $proxyFactory;

    /** @var Meta\Repository The meta repository to use. */
    private $metaRepository;

    /** @var \Everyman\Neo4j\Batch A holder for the current Everyman batch. */
    private $batch;

    /** @var array Storage for node entities to create/update on flush. */
    private $nodeEntities = array();

    /** @var array Storage for node entities to remove on flush. */
    private $nodeEntitiesToRemove = array();

    /** @var array Storage for relation entities to create/update on flush. */
    private $relationEntities = array();

    /** @var array Storage for relation entities to remove on flush. */
    private $relationEntitiesToRemove = array();

    /** @var array List of relations by start node. */
    private $startNodeRelations = array();

    /** @var array List of relations by end node. */
    private $endNodeRelations = array();

    /** @var array List of Everyman node objects. */
    private $nodes = array();

    /** @var array List of Everyman relation objects. */
    private $relations = array();

    /** @var array List of repositories for querying. */
    private $repositories = array();

    /** @var array Storage for loaded node entities. */
    private $loadedNodes = array();

    /** @var array Storage for loaded relation entities. */
    private $loadedRelations = array();

    /** @var callable Date generation function to use. */
    private $dateGenerator;

    /** @var array Array of event handlers to call during various tasks. */
    private $eventHandlers = array();

    /**
     * Initialize the entity manager using the provided configuration.
     *
     * Configuration options are detailed in the Configuration class, and can be passed as an array of values instead
     * of a Configuration object.
     * 
     * @param Configuration|array $configuration Various information about how the entity manager should behave.
     * @throws Exception Thrown when argument is not a configuration object or array.
     */
    function __construct($configuration = null)
    {
        //Check if there was a configuration provided
        if (is_null($configuration))
        {
            //Create a new default config
            $configuration = new Configuration;
        }

        //If an array was provided, create a new config based on that
        elseif (is_array($configuration))
        {
            $configuration = new Configuration($configuration);
        }

        //If the configuration is not an array, and is not empty, it must be a configuration object
        elseif (! $configuration instanceof Configuration)
        {
            //Not a configuration object
            throw new Exception('Provided argument must be a Configuration object or an array.');
        }

        //Get a proxy factory
        $this->proxyFactory = $configuration->getProxyFactory();

        //Get the database connection from the client
        $this->client = $configuration->getClient();

        //Get a meta repository from the configuration
        $this->metaRepository = $configuration->getMetaRepository();

        //Create a data generator function
        $this->dateGenerator = function () {
            $currentDate = new \DateTime;
            return $currentDate->format('Y-m-d H:i:s');
        };

    }


    /**
     * Marks an entity for addition/modification in the database.
     *
     * This adds the specified entity to the add/update list, to be added or updated on the next <code>flush()</code>
     * command.
     *
     * @api
     * @param object $entity Any object providing the correct Entity annotations.
     */
    function persist($entity)
    {
        //Get meta info for the class (Also makes sure it's a node/relation and throws an exception)
        $meta = $this->getMeta($entity);

        //Get a hash of this entity
        $hash = $this->getHash($entity);

        //Store a node entity
        if($meta instanceof \LRezek\Neo4PHP\Meta\Node)
        {
            //Don't persist it again if it's already been done
            if(! array_key_exists($hash, $this->nodeEntities))
            {
                $this->nodeEntities[$hash] = $entity;
            }
        }

        //Store a relation entity
        else
        {
            //Store the entity
            $this->relationEntities[$hash] = $entity;

            //Get the start and end nodes
            $start = $meta->getStart()->getValue($entity);
            $end = $meta->getEnd()->getValue($entity);

            //If there is a start node, save it for later
            if($start)
            {
                $startHash = $this->getHash($start);
                $this->persist($start);

                //Save the relation to the nodes info
                if(! array_key_exists($startHash, $this->startNodeRelations))
                {
                    //Initialize the array
                    $this->startNodeRelations[$startHash] = array();
                }

                //Add this relation to the list for the nodes
                $this->startNodeRelations[$startHash][] = $entity;
            }

            //If there is a end node, save it for later
            if($end)
            {
                $endHash = $this->getHash($end);
                $this->persist($end);

                if(! array_key_exists($endHash, $this->endNodeRelations))
                {
                    //Initialize the array
                    $this->endNodeRelations[$endHash] = array();
                }

                $this->endNodeRelations[$endHash][] = $entity;
            }
        }

    }

    /**
     * Marks an entity for removal from the database.
     *
     * This adds the specified entity to the remove list, to be removed on the next <code>flush()</code> command.
     *
     * @api
     * @param $entity
     */
    function remove($entity)
    {
        //Get meta information
        $meta = $this->getMeta($entity);

        //Get a hash of this entity
        $hash = $this->getHash($entity);

        if($meta instanceof \LRezek\Neo4PHP\Meta\Node)
        {
            $this->nodeEntitiesToRemove[$hash] = $entity;
        }

        else
        {
            $this->relationEntitiesToRemove[$hash] = $entity;
        }
    }

    /**
     * Commits persistent entities to the database.
     *
     * Commits all entities to be added, modified, or removed to the database, as well as writing indexes for each entity.
     * nodes are added as required, and if you persist a relationship without persisting the start or end node, those are
     * added as well.
     *
     * @api
     */
    function flush()
    {

        //Writes all entities to the DB
        $this->writeEntities();

        //Write node labels
        $this->writeLabels();

        //Write indexes
        $this->writeIndexes();

        //delete removed entities
        $this->removeEntities();

        $this->nodeEntities = array();
        $this->relationEntities = array();

        $this->nodeEntitiesToRemove = array();
        $this->relationEntitiesToRemove = array();

        $this->startNodeRelations = array();
        $this->endNodeRelations = array();
    }


    /**
     * Removes entities in the remove list from the database.
     *
     * This removes entities persisted via the <code>remove($entity)</code> command. If a node is removed, all of its
     * relations are deleted from the database first.
     */
    private function removeEntities()
    {
        //Begin the database batch
        $this->beginBatch();

        foreach($this->nodeEntitiesToRemove as $node)
        {

            //Get the primary key
            $meta = $this->getMeta($node);
            $id = $meta->getPrimaryKey()->getValue($node);

            if($id)
            {

                //Grab the node from the database
                $entity = $this->client->getNode($id);

                //Delete all the nodes relations
                $relationships = $entity->getRelationships();

                foreach ($relationships as $relationship)
                {
                    $relationship->delete();
                }

                //Delete index information
                $class = $meta->getName();
                $index = $this->getRepository($class)->getIndex();
                $index->remove($entity);

                //Delete the entity
                $entity->delete();

            }
        }

        foreach($this->relationEntitiesToRemove as $relation)
        {
            //Get the primary key
            $meta = $this->getMeta($relation);
            $id = $meta->getPrimaryKey()->getValue($relation);

            if($id)
            {
                $entity = $this->client->getRelationship($id);

                //Delete index information
                $class = $meta->getName();
                $index = $this->getRepository($class)->getIndex();
                $index->remove($entity);

                //Delete the entity
                $entity->delete();
            }
        }

        //Commit the database batch
        $this->commitBatch();
    }

    /**
     * Loops through persistent entities and stores them in the database.
     *
     * Loops through entities in the list of entities to persist, and converts them to real node/relation objects. These
     * objects are then written to the database using Everyman Neo4J calls. All the entities are done at once in a batch.
     * When each node is created, entities that start or end on it are updated to point to the real node object. If a
     * relation doesn't have a matching node in the entities list, it is created and added.
     */
    private function writeEntities()
    {

        //Begin the database batch
        $this->beginBatch();

        //Loop through node entities
        foreach ($this->nodeEntities as $node)
        {
            //Get entities hash and meta info
            $hash = $this->getHash($node);
            $meta = $this->getMeta($node);

            //Create the node from the entity and save it to the database
            $this->nodes[$hash] = $this->createNode($node)->save();

            //Make relations this node starts on go to the node
            if(array_key_exists($hash, $this->startNodeRelations))
            {
                foreach($this->startNodeRelations[$hash] as $relation)
                {
                    //Get the relations hash
                    $relHash = $this->getHash($relation);

                    //Get relation meta
                    $relMeta = $this->getMeta($this->relationEntities[$relHash]);

                    //Move the start node to an everyman node
                    $prop = $relMeta->getStart();
                    $prop->setValue($this->relationEntities[$relHash], $this->nodes[$hash]);
                }
            }


            //Make relations this node ends on go to the node
            if(array_key_exists($hash, $this->endNodeRelations))
            {
                foreach($this->endNodeRelations[$hash] as $relation)
                {
                    //Get the relations hash
                    $relHash = $this->getHash($relation);

                    //Get relation meta
                    $relMeta = $this->getMeta($this->relationEntities[$relHash]);

                    //Move the end node to an everyman node
                    $prop = $relMeta->getEnd();
                    $prop->setValue($this->relationEntities[$relHash], $this->nodes[$hash]);
                }
            }

            //Trigger node creation event (if it's defined...)
            $this->triggerEvent(self::RELATION_CREATE, $node, $this->nodes[$hash]);
        }


        //Loop through Entities and create relations, this is done after node creation
        foreach ($this->relationEntities as $relation)
        {
            //Get entities hash and meta info
            $hash = $this->getHash($relation);

            //Create and save the relationship
            $this->relations[$hash] = $this->createRelation($relation)->save();

            //Trigger relation creation event (if it's defined...)
            $this->triggerEvent(self::RELATION_CREATE, $relation, $this->relations[$hash]);
        }

        //Commit the database batch
        $this->commitBatch();

    }


    /**
     * Creates a Everyman node object from the given entity.
     *
     * This creates a Everyman node object from the entity supplied, unless a node with the same primary key already
     * exists. In this case, it updates the properties of the existing node. Some properties are updated or created
     * automatically; Namely, the primary key, the class, the creation date, and the update date.
     *
     * @param mixed $entity The entity to convert to a node.
     * @return \Everyman\Neo4j\Node The created node.
     */
    private function createNode($entity)
    {
        //Get meta
        $meta = $this->getMeta($entity);

        //Get primary key
        $pk = $meta->getPrimaryKey();
        $id = $pk->getValue($entity);

        //Check if it already exists
        if ($id)
        {
            $node = $this->client->getNode($id);
        }

        //Create it, it doesn't exist
        else
        {
            $node = $this->client->makeNode();
//                ->setProperty('class', $meta->getName());
        }


        //Add all the properties
        foreach ($meta->getProperties() as $property) {
            $result = $property->getValue($entity);

            $node->setProperty($property->getName(), $result);
        }

        $currentDate = $this->getCurrentDate();

        //Add the creation date
        if (! $id)
        {
            $node->setProperty('creationDate', $currentDate);
        }

        //Add the update date
        $node->setProperty('updateDate', $currentDate);

        return $node;
    }

    /**
     * Creates a Everyman relationship object from the given entity.
     *
     * This creates a Everyman relationship object from the entity supplied, unless a relation with the same primary key
     * already exists. In this case, it updates the properties of the existing relationship. Some properties are updated
     * or created automatically; Namely, the primary key, the type (which is set as the entity class), the creation date,
     * and the update date.
     *
     * @param mixed $entity The entity to convert to a relationship.
     * @throws Exception If no start/end node supplied for a new relation.
     * @return \Everyman\Neo4j\Relationship The created relationship.
     */
    private function createRelation($entity)
    {
        $meta = $this->getMeta($entity);
        $pk = $meta->getPrimaryKey();
        $id = $pk->getValue($entity);

        $start = $meta->getStart()->getValue($entity);
        $end = $meta->getEnd()->getValue($entity);

        //If it already has an ID, get the relation from the DB
        if ($id)
        {
            $relation = $this->client->getRelationship($id);

            //A start node was supplied
            if($start)
            {
                //Get the start node id
                $nid = $start->getId();

                //Check if the requested start node is different than the present one.
                if($nid != $relation->getStartNode()->getId())
                {
                    //$relation->setStartNode($start);
                    throw new Exception("You can't change the start node of a saved relation.");
                }

            }

            //A end node was supplied
            if($end)
            {
                //Get the end node id
                $nid = $end->getId();

                //If the attached nodes ID is different than the id the relation currently points to
                if($nid != $relation->getEndNode()->getId())
                {
                    //$relation->setEndNode($end);
                    throw new Exception("You can't change the end node of a saved relation.");
                }

            }

        }

        //Create a relationship with the correct type
        else
        {
            $relation = $this->client->makeRelationship()
                ->setType($meta->getName());

            //No start node
            if(!$start)
            {
                throw new Exception("No start node supplied for new " . $meta->getName() . " relation.");
            }

            //No end node
            if(!$end)
            {
                throw new Exception("No end node supplied for new " . $meta->getName() . " relation.");
            }

            //Set the nodes
            $relation->setStartNode($start)->setEndNode($end);
        }

        //Copy all the properties
        foreach ($meta->getProperties() as $property)
        {
            $result = $property->getValue($entity);

            $relation->setProperty($property->getName(), $result);
        }

        $currentDate = $this->getCurrentDate();

        //Set the creation date if this relation didn't exist
        if (! $id)
        {
            $relation->setProperty('creationDate', $currentDate);
        }

        //Set the update time
        $relation->setProperty('updateDate', $currentDate);

        return $relation;
    }


    /**
     * Writes labels for every node that was saved or updated.
     *
     * Writes node labels in a batch, looping through all node entities and labelling them. This happens after the
     * nodes have already been saved, so the Everyman nodes are available in <code>$this->nodes</code>.
     */
    private function writeLabels()
    {
        $this->beginBatch();

        foreach($this->nodeEntities as $node)
        {
            $meta = $this->getMeta($node);

            //Add the class name as a label
            $label = $this->client->makeLabel($meta->getName());
            $this->nodes[$this->getHash($node)]->addLabels(array($label));

        }

        $this->commitBatch();
    }


    /**
     * Creates a Everyman index based on the class name supplied.
     *
     * @param string $className The class to create an index for.
     * @return NodeIndex|RelationshipIndex The index object.
     */
    function createIndex($className)
    {
        //Get meta info for the class
        $meta = $this->metaRepository->fromClass($className);

        //Create a node index
        if($meta instanceof \LRezek\Neo4PHP\Meta\Node)
        {
            return new NodeIndex($this->client, $className);
        }

        else
        {
            return new RelationshipIndex($this->client, $className);
        }

    }

    /**
     * Adds indexes for the entity to the indexes stored in the classes repository.
     *
     * @param mixed $entity The entity to write index's for.
     */
    private function index($entity)
    {
        //Get meta info
        $meta = $this->getMeta($entity);

        //Get class name
        $class = $meta->getName();

        //Get the index from the repository
        $index = $this->getRepository($class)->getIndex();

        //Get the loaded node if it's a node
        if($meta instanceof \LRezek\Neo4PHP\Meta\Node)
        {
            $en = $this->getEverymanNode($entity);
        }

        //Otherwise, get the loaded relation
        else
        {
            $en = $this->getEverymanRelation($entity);
        }

        //Add all indexed properties
        foreach ($meta->getIndexedProperties() as $property)
        {
            $index->add($en, $property->getName(), $property->getValue($entity));
        }

        //Get the primary key
        $pk = $meta->getPrimaryKey();
        $name = $pk->getName();
        $id = $pk->getValue($entity);

        //Add the primary key to the index
        $index->add($en, $name, $id);
    }

    /**
     * Writes index information to the database.
     *
     * This writes index information for all entities to their repositories, and then updates database indexes based on
     * those. This is all done in a batch.
     */
    private function writeIndexes()
    {
        //Begin database batch
        $this->beginBatch();

        //Index all node entities
        foreach ($this->nodeEntities as $entity)
        {
            $this->index($entity);
        }

        //Index all relation entities
        foreach ($this->relationEntities as $entity)
        {
            $this->index($entity);
        }

        //Write all repository indexes
        foreach ($this->repositories as $repository)
        {
            $repository->writeIndex();
        }

        //Commit batch to the database
        $this->commitBatch();
    }


    /**
     * Gets nodes that have been loaded (converted from entities to Everyman nodes).
     *
     * @param mixed $entity The entity to get the node for.
     * @return \Everyman\Neo4J\Node The entities corresponding node.
     */
    private function getEverymanNode($entity)
    {
        return $this->nodes[$this->getHash($entity)];
    }

    /**
     * Gets relations that have been loaded (converted from entities to Everyman relationships).
     *
     * @param mixed $entity The entity to get the relationship for.
     * @return \Everyman\Neo4J\Relationship The entities corresponding relationship.
     */
    private function getEverymanRelation($entity)
    {
        return $this->relations[$this->getHash($entity)];
    }

    /**
     * Obtain the entity repository for the specified class.
     *
     * Obtain an entity repository for a single class. The repository provides
     * multiple methods to access nodes and can be extended per entity by
     * specifying the correct annotation.
     *
     * @param string $class Fully qualified class name
     * @return mixed The repository for the class
     * @throws Exception Thrown if the repository class does not extend the base repository class
     * @api
     */
    function getRepository($class)
    {
        //If a repository doesn't exist for this class, make a new one!
        if (! isset($this->repositories[$class]))
        {
            //Get meta info for the class
            $meta = $this->metaRepository->fromClass($class);

            //Get the repository class and make a new instance of that class
            $repositoryClass = $meta->getRepositoryClass();
            $repository = new $repositoryClass($this, $meta);

            //Make sure you're extending repository.
            if (! $repository instanceof Repository) {
                throw new Exception("Requested repository class $repositoryClass does not extend the base repository class.");
            }

            //Save the repo
            $this->repositories[$class] = $repository;
        }

        return $this->repositories[$class];
    }

    /**
     * Enables underscore convention for getRepository.
     *
     * @param string $class Fully qualified class name
     * @return mixed The repository for the class
     * @throws Exception Thrown if the repository class does not extend the base repository class
     * @api
     */
    function get_repository($class)
    {
        return $this->getRepository($class);
    }

    /**
     * Loads a node using a proxy.
     *
     * Loads a node using a proxy, and stores it in the appropriate places in the entity manager.
     *
     * @param \Everyman\Neo4J\Node $node The node to load.
     * @return mixed The entity itself.
     */
    function loadNode($node)
    {
        //If the node isn't already loaded
        if (! isset($this->loadedNodes[$node->getId()]))
        {
            //Get the nodes class name (from label)
            $labels = $this->client->getLabels($node);
            $class = $labels[0]->getName();

            //Create a proxy entity
            $entity = $this->proxyFactory->fromNode($node, $this->metaRepository, $class);

            $this->loadedNodes[$node->getId()] = $entity;
            $this->nodes[$this->getHash($entity)] = $node;
        }

        return $this->loadedNodes[$node->getId()];
    }

    /**
     * Loads a relation using a proxy.
     *
     * Loads a relation using a proxy, and stores it in the appropriate places in the entity manager.
     *
     * @param \Everyman\Neo4J\Relationship $relation The relation to load.
     * @return mixed The entity itself.
     */
    function loadRelation($relation)
    {
        //If the node isn't already loaded
        if (! isset($this->loadedRelations[$relation->getId()]))
        {
            //Create a proxy entity
            $em = $this;
            $entity = $this->proxyFactory->fromRelation($relation, $this->metaRepository, function ($node) use ($em) {
                return $em->loadNode($node);
            });

            $this->loadedRelations[$relation->getId()] = $entity;
            $this->relations[$this->getHash($entity)] = $relation;
        }

        return $this->loadedRelations[$relation->getId()];
    }

    /**
     * Reload an entity. Exchanges an raw entity or an invalid proxy with an initialized
     * proxy.
     *
     * @param object $entity Any entity or entity proxy
     * @throws Exception
     * @return mixed
     */
    function reload($entity)
    {
        //Get meta and hash for entity
        $hash = $this->getHash($entity);
        $meta = $this->getMeta($entity);

        //Get the primary key
        $id = $meta->getPrimaryKey()->getValue($entity);

        if($meta instanceof \LRezek\Neo4PHP\Meta\Node)
        {
            //Is cached
            if(isset($this->nodes[$hash]))
            {
                return $this->loadNode($this->nodes[$hash]);
            }

            //Not in the cache, but has an id, get the node and load it.
            elseif($id)
            {
                $node = $this->client->getNode($id);
                return $this->loadNode($node);
            }

            else
            {
                throw new Exception('Cannot reload an unsaved node.');
            }
        }

        else
        {
            if(isset($this->relations[$hash]))
            {
                return $this->loadRelation($this->relations[$hash]);
            }

            //Not in the cache, but has an id, get the node and load it.
            elseif($id)
            {
                $rel = $this->client->getRelationship($id);
                return $this->loadRelation($rel);
            }

            else
            {
                throw new Exception('Cannot reload an unsaved relation.');
            }
        }
    }

    /**
     * Clear the entity cache
     */
    function clear()
    {
        $this->loadedNodes = array();
        $this->loadedRelations = array();
    }


    /**
     * Allows for registration of custom events on query completion.
     *
     * @param $eventName
     * @param $callback
     */
    function registerEvent($eventName, $callback)
    {
        $this->eventHandlers[$eventName][] = $callback;
    }

    /**
     * Triggers an event held in the everntHandlers array.
     *
     * @param string $eventName Name of the event.
     * @param array $data Parameters to pass to the event handler.
     */
    private function triggerEvent($eventName, $data)
    {
        if (isset($this->eventHandlers[$eventName]))
        {
            $args = func_get_args();
            array_shift($args);

            foreach ($this->eventHandlers[$eventName] as $callback)
            {
                $clone = $args;
                call_user_func_array($callback, $clone);
            }
        }
    }

    /**
     * Provides a Cypher query builder.
     *
     * @return Query\Cypher
     */
    function createCypherQuery()
    {
        return new Query\Cypher($this);
    }

    /**
     * Raw cypher query execution.
     *
     * @param string $string The query string.
     * @param array $parameters The arguments to bind with the query.
     * @throws Exception If the query fails.
     * @return \Everyman\Neo4j\Query\ResultSet The everyman result set.
     */
    function cypherQuery($string, $parameters)
    {
        try {

            $start = microtime(true);

            $query = new InternalCypherQuery($this->client, $string, $parameters);
            $rs = $query->getResultSet();

            $time = microtime(true) - $start;
            $this->triggerEvent(self::QUERY_RUN, $query, $parameters, $time);

            return $rs;
        }

        catch (\Everyman\Neo4j\Exception $e)
        {
            $message = $e->getMessage();
            preg_match('/\[message\] => (.*)/', $message, $parts);
            throw new Exception('Query execution failed: ' . $parts[1], 0, $e, $query);
        }
    }


    /**
     * Creates a hash of the given object.
     *
     * @param $object Object to hash.
     * @return string Hash.
     */
    public function getHash($object)
    {
        return spl_object_hash($object);
    }

    /**
     * Gets meta information for an entity.
     *
     * @param Node|Relation $entity The entity.
     * @return mixed Meta information from meta repository.
     */
    private function getMeta($entity)
    {
        return $this->metaRepository->fromClass(get_class($entity));
    }

    /**
     * Gets the meta repository used by the entity manager.
     *
     * @return Meta\Repository
     */
    public function getMetaRepository()
    {
        return $this->metaRepository;
    }

    /**
     * Gets the current date.
     *
     * @return mixed The date, generated by the date generator.
     */
    private function getCurrentDate()
    {
        $gen = $this->dateGenerator;
        return $gen();
    }

    /**
     * Returns the Client
     *
     * @return \Everyman\Neo4j\Client
     */
    public function getClient()
    {
        return $this->client;
    }


    /**
     * Starts a database batch.
     */
    private function beginBatch() {

        //Start up the batch
        $this->batch = $this->client->startBatch();

    }

    /**
     * Commits a database batch.
     */
    private function commitBatch() {

        //Check if there are any operations and commit the batch
        if (count($this->batch->getOperations()))
        {

            $this->client->commitBatch();
        }

        //There are no operations, just end the batch
        else
        {
            $this->client->endBatch();
        }

        //Delete the batch
        $this->batch = null;

    }
}

