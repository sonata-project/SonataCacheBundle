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
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;

class DoctrineORMListener implements EventSubscriber
{
    protected $caches = array();

    protected $collectionIdentifiers;

    /**
     * @param ModelCollectionIdentifiers $collectionIdentifiers
     * @param array                      $caches
     */
    public function __construct(ModelCollectionIdentifiers $collectionIdentifiers, $caches)
    {
        $this->collectionIdentifiers = $collectionIdentifiers;

        foreach ($caches as $cache) {
            $this->addCache($cache);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents()
    {
        return array(
            Events::preRemove,
            Events::preUpdate
        );
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        $this->flush($args);
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args)
    {
        $this->flush($args);
    }

    /**
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     *
     * @return mixed
     */
    protected function flush(LifecycleEventArgs $args)
    {
        $identifier = $this->collectionIdentifiers->getIdentifier($args->getEntity());

        if ($identifier === false) {
            return;
        }

        $parameters = array(
            get_class($args->getEntity()) => $identifier
        );

        foreach ($this->caches as $cache) {
            $cache->flush($parameters);
        }
    }

    /**
     * @param \Sonata\CacheBundle\Cache\CacheInterface $cache
     *
     * @return mixed
     */
    public function addCache(CacheInterface $cache)
    {
        if (!$cache->isContextual()) {
            return;
        }

        $this->caches[] = $cache;
    }
}