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

    public function setUp(): void
    {
        if (!\function_exists('apcu_store')) {
            $this->markTestSkipped('APC is not installed');
        }

        if (0 == ini_get('apcu.enable_cli')) {
            $this->markTestSkipped('APC is not enabled in cli, please add apcu.enable_cli=On into the apcu.ini file');
        }

        $this->router = $this->createMock(RouterInterface::class);

        $this->cache = new ApcCache($this->router, 'token', 'prefix_', [], []);
    }

    public function testInitCache(): void
    {
        $this->assertTrue($this->cache->flush([]));
        $this->assertTrue($this->cache->flushAll());

        $cacheElement = $this->cache->set(['id' => 7], 'data');

        $this->assertInstanceOf(CacheElement::class, $cacheElement);

        $this->assertTrue($this->cache->has(['id' => 7]));

        $this->assertFalse($this->cache->has(['id' => 8]));

        $cacheElement = $this->cache->get(['id' => 7]);

        $this->assertInstanceOf(CacheElement::class, $cacheElement);
    }

    public function testGetUrl(): void
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('sonata_cache_apc'), $this->equalTo(['token' => 'token']))
            ->will($this->returnValue('/sonata/cache/apc/token'));

        $method = new \ReflectionMethod($this->cache, 'getUrl');
        $method->setAccessible(true);

        $this->assertEquals('/sonata/cache/apc/token', $method->invoke($this->cache));
    }
}
