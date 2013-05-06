<?php

/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CacheCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        $caches = array();

        foreach ($container->findTaggedServiceIds('sonata.cache') as $id => $attributes) {
            if (!$container->hasDefinition($id)) {
                continue;
            }

            $caches[$id] = new Reference($id);
        }

        if ($container->hasDefinition('sonata.cache.orm.event_subscriber.default')) {
            $container->getDefinition('sonata.cache.orm.event_subscriber.default')
                ->replaceArgument(1, $caches);
        }

        if ($container->hasDefinition('sonata.cache.phpcr_odm.event_subscriber.default')) {
            $container->getDefinition('sonata.cache.phpcr_odm.event_subscriber.default')
                ->replaceArgument(1, $caches);
        }

        if ($container->hasDefinition('sonata.cache.manager')) {
            $container->getDefinition('sonata.cache.manager')
                ->replaceArgument(1, $caches);
        }
    }
}