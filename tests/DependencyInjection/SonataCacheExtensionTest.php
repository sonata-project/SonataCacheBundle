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

namespace Sonata\CacheBundle\Tests\DependencyInjection\Configuration;

use PHPUnit\Framework\TestCase;
use Sonata\CacheBundle\DependencyInjection\SonataCacheExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Functional test for SonataCacheExtension.
 *
 * @author Fabien Bourigault <bourigaultfabien@gmail.com>
 */
class SonataCacheExtensionTest extends TestCase
{
    public function testUseCacheInvalidationDoctrineListeners(): void
    {
        $container = new ContainerBuilder();
        $extension = new SonataCacheExtension();
        $extension->load([], $container);

        $this->assertTrue($container->hasDefinition('sonata.cache.orm.event_subscriber'));
        $this->assertTrue($container->hasDefinition('sonata.cache.phpcr_odm.event_subscriber'));
    }
}
