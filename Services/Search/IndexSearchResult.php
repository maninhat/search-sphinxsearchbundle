<?php

namespace Search\SphinxsearchBundle\Services\Search;
use \Doctrine\ORM\EntityManager;
use \Doctrine\Common\Collections\ArrayCollection;
use Search\SphinxsearchBundle\Services\Exception\MappingException;

class IndexSearchResult implements SearchResultInterface
{

    /**
     * @var string
     */
    private $indexName;

    /**
     * @var array
     */
    private $rawResults;

    /**
     * @var int
     */
    private $totalFound;


    /**
     * @var array
     */
    private $matches;

    /**
     * @var MappingCollection
     */
    private $mapping;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    public function __construct($indexName, $rawResults, MappingCollection $mapping =null , EntityManager $em = null)
    {

        $this->rawResults = $rawResults;
        $this->indexName = $indexName;
        $this->totalFound = $rawResults['total_found'];
        $this->mapping = $mapping;
        $this->em = $em;
        //die('IndexSearchResult');
        // Normalize sphinxsearch result array
        if (array_key_exists('matches', $rawResults)) {
            $rawMatches = $rawResults['matches'];
            $this->matches = array();
            foreach ($rawMatches as $id => $match) {
                $match['attrs']['id'] = $id;
                $this->matches [] = $match;
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

    /**
     * @return ArrayCollection will return collection of objects if it matched them
     */
    public function getMappedMatches()
    {
        $mapping = $this->mapping;
        $parameters = $mapping->getAvailableParameters();
        $matches = $this->matches;
        $Result = new ArrayCollection();

        foreach ($matches as $match) {
            $attrs = array_keys($match['attrs']);
            $matchedAttrs = array_intersect($attrs, $parameters);
            if (!count($matchedAttrs)) continue;
            foreach ($matchedAttrs as $matchedAttr) {
                $value = $match['attrs'][$matchedAttr];
                $repoName = $mapping->findRepository($matchedAttr, $value);
                if ($repoName) {
                    $repo = $this->em->getRepository($repoName);
                    $element = $repo->findOneById($match['attrs']['id']);
                    if ($element) {
                        if($element instanceof  SearchableInterface)
                        {
                            $Result->add($element);
                        }
                        else{
                            throw new  MappingException(sprintf('Object "%s" don\'t implements interface "SearchableInterface".',get_class($element)));
                        }
                    }
                }
            }

        }
        return $Result;
    }
}
