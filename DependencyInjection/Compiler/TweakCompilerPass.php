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

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class TweakCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sonata.cache.orm.event_subscriber.default')) {
            $ormListener = $container->getDefinition('sonata.cache.orm.event_subscriber.default');
            foreach ($container->findTaggedServiceIds('sonata.cache') as $id => $attributes) {
                if (!$container->hasDefinition($id)) {
                    continue;
                }

                $ormListener->addMethodCall('addCache', array(new Reference($id)));
            }
        }
    }
}
