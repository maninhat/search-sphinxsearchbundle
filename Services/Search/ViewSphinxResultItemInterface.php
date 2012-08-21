<?php

namespace Search\SphinxsearchBundle\Services\Search;



interface ViewSphinxResultItemInterface
{
    /**
     * @abstract
     * @return string
     */
    public function getSearchLabel();

    /**
     * @abstract
     * @return string
     */
    public function getSearchDescription();

    /**
     * @abstract
     * @return string
     */
    public function getSearchRoute();

    /**
     * @abstract
     * @return array
     */
    public function getSearchRouteParameters();
}
