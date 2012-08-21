<?php

namespace Search\SphinxsearchBundle\Services\Search;
use Doctrine\ORM\EntityManager;
use \Doctrine\Common\Collections\ArrayCollection;

class MappingCollection extends ArrayCollection
{
  //  protected $_elements;


    public function getAvailableParameters(){
        $parameters=array();
        foreach($this->toArray() as $element){
            if(!in_array($element['parameter'],$parameters)){
                 $parameters[]=$element['parameter'];
            }
        }
        return $parameters;
    }

    /**
     * @param $parameter
     * @param $value
     * return string Repository name
     */
    public function findRepository($parameter,$value){

        foreach($this->toArray() as $element){
            if($element['parameter']==$parameter && $element['value']==$value){
                return $element['repository'];
            }
        }
        return false;
    }
}
