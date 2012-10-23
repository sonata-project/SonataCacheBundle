<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Invalidation;

use Sonata\CacheBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Invalidation\ModelCollectionIdentifiers;

use Doctrine\Common\EventSubscriber;
use Doctrine\ODM\PHPCR\Event;
use Doctrine\ODM\PHPCR\Event\LifecycleEventArgs;

class DoctrinePHPCRODMListener implements EventSubscriber
{
    protected $caches = array();

    protected $collectionIdentifiers;

    public function __construct(ModelCollectionIdentifiers $collectionIdentifiers, $caches)
    {
        $this->collectionIdentifiers = $collectionIdentifiers;

        foreach ($caches as $cache) {
            $this->addCache($cache);
        }
    }

    public function getSubscribedEvents()
    {
        return array(
            Event::preRemove,
            Event::preUpdate
        );
    }

    public function preRemove(LifecycleEventArgs $args)
    {
        $this->flush($args);
    }

    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->flush($args);
    }

    protected function flush(LifecycleEventArgs $args)
    {
        $identifier = $this->collectionIdentifiers->getIdentifier($args->getDocument());

        if ($identifier === false) {
            return;
        }

        $parameters = array(
            get_class($args->getDocument()) => $identifier
        );

        foreach ($this->caches as $cache) {
            $cache->flush($parameters);
        }
    }

    public function addCache(CacheInterface $cache)
    {
        if (!$cache->isContextual()) {
            return;
        }

        $this->caches[] = $cache;
    }
}