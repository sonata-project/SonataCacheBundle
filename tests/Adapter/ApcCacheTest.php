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

namespace Sonata\CacheBundle\Tests\Adapter;

use PHPUnit\Framework\TestCase;
use Sonata\Cache\CacheElement;
use Sonata\CacheBundle\Adapter\ApcCache;
use Symfony\Component\Routing\RouterInterface;

class ApcCacheTest extends TestCase
{
    private $router;

    /**
     * @var ApcCache
     */
    private $cache;

    protected function setUp(): void
    {
        if (!\function_exists('apc_store')) {
            static::markTestSkipped('APC is not installed');
        }

        if ('On' !== ini_get('apc.enable_cli')) {
            static::markTestSkipped('APC is not enabled in cli, please add apcu.enable_cli=On into the apcu.ini file');
        }

        $this->router = $this->createMock(RouterInterface::class);

        $this->cache = new ApcCache($this->router, 'token', 'prefix_', [], []);
    }

    public function testInitCache(): void
    {
        static::assertTrue($this->cache->flush([]));
        static::assertTrue($this->cache->flushAll());

        $cacheElement = $this->cache->set(['id' => 7], 'data');

        static::assertInstanceOf(CacheElement::class, $cacheElement);

        static::assertTrue($this->cache->has(['id' => 7]));

        static::assertFalse($this->cache->has(['id' => 8]));

        $cacheElement = $this->cache->get(['id' => 7]);

        static::assertInstanceOf(CacheElement::class, $cacheElement);
    }

    public function testGetUrl(): void
    {
        $this->router
            ->expects(static::once())
            ->method('generate')
            ->with(static::equalTo('sonata_cache_apc'), static::equalTo(['token' => 'token']))
            ->willReturn('/sonata/cache/apc/token');

        $method = new \ReflectionMethod($this->cache, 'getUrl');
        $method->setAccessible(true);

        static::assertSame('/sonata/cache/apc/token', $method->invoke($this->cache));
    }
}
