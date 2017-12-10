<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Invalidation;

use Doctrine\Common\EventSubscriber;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Doctrine\ODM\PHPCR\Event;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DoctrinePHPCRODMListenerContainerAware implements EventSubscriber
{
    protected $listener;

    protected $service;

    /**
     * @param ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     * @param $service
     */
    public function __construct(ContainerInterface $container, string $service)
    {
        $this->container = $container;
        $this->service = $service;
    }

    /**
     * @return array
     */
    public function getSubscribedEvents(): array
    {
        return [
            Event::preRemove,
            Event::preUpdate,
        ];
    }

    /**
     * @param $args
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        $this->load();

        $this->listener->preRemove($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->load();

        $this->listener->preUpdate($args);
    }

    private function load(): void
    {
        if ($this->listener) {
            return;
        }

        $this->listener = $this->container->get($this->service);
    }
}
