<?php

namespace Search\SphinxsearchBundle\Services\Search;

class IndexSearchResult implements SearchResultInterface
{
    private $indexName;

    private $rawResults;

    private $totalFound;

    private $matches;


    public function __construct($indexName, $rawResults)
    {
        $this->rawResults = $rawResults;
        $this->indexName = $indexName;
        $this->totalFound = $rawResults['total_found'];

        // Normalize sphinxsearch result array
        if (array_key_exists('matches', $rawResults)) {
            $rawMatches = $rawResults['matches'];
            $this->matches = array();
            foreach ($rawMatches as $id => $match) {
                $match['attrs']['id'] = $id;
                $this->matches []= $match;
            }
        } else {
            $this->matches = array();
        }
    }

    public function getIndexName()
    {
        return $this->indexName;
    }

    public function getTotalFound()
    {
        return $this->totalFound;
    }

    public function getCurrentFound()
    {
        return count($this->matches);
    }

    public function getMatches()
    {
        return $this->matches;
    }
}
