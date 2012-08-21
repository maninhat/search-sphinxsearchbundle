<?php

namespace Search\SphinxsearchBundle\Services\Search;
use Doctrine\ORM\EntityManager;

class ResultCollection implements CollectionInterface
{
    /**
     * Array of SearchResultInterface
     *
     * @var Array
     */
    private $results;


    public function __construct($rawResults, array $mapping = array(), EntityManager $em = null)
    {
        echo 'result_collect';
        foreach ($rawResults as $indexName => $result) {
            $this->results[$indexName] = new IndexSearchResult($indexName, $result,$mapping,$em);
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->results);
    }

    public function count()
    {
        return count($this->results);
    }

    public function get($indexName)
    {
        return $this->results[$indexName];
    }
}
