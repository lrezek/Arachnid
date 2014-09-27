<?php
namespace LRezek\Arachnid\Tests\Repo;
use LRezek\Arachnid\Repository as BaseRepository;

class CustomRepo extends BaseRepository
{
    public function getQuery()
    {
        return $this->createCypherQuery();
    }

    public function get_query()
    {
        return $this->create_cypher_query();
    }
}