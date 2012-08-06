<?php

namespace Search\SphinxsearchBundle\Subscriber;

use Symfony\Component\Finder\Finder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Search\SphinxsearchBundle\Services\Search\SearchResultInterface;

/**
 * Sphinxsearch result subscriber
 */
class SphinxsearchResultSubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if ($event->target instanceof SearchResultInterface) {
            $event->count = $event->target->getTotalFound();
            $event->items = $event->target->getMatches();
            $event->stopPropagation();
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            'knp_pager.items' => array('items', 1/*increased priority to override any internal*/)
        );
    }
}

