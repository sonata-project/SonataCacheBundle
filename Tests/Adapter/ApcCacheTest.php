<?php

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
use Sonata\CacheBundle\Adapter\ApcCache;

class ApcCacheTest extends TestCase
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var ApcCache
     */
    private $cache;

    public function setUp(): void
    {
        if (!function_exists('apcu_store')) {
            $this->markTestSkipped('APC is not installed');
        }

        if (ini_get('apcu.enable_cli') == 0) {
            $this->markTestSkipped('APC is not enabled in cli, please add apcu.enable_cli=On into the apcu.ini file');
        }

        $this->router = $this->createMock('Symfony\Component\Routing\RouterInterface');

        $this->cache = new ApcCache($this->router, 'token', 'prefix_', [], []);
    }

    public function testInitCache(): void
    {
        $this->assertTrue($this->cache->flush([]));
        $this->assertTrue($this->cache->flushAll());

        $cacheElement = $this->cache->set(['id' => 7], 'data');

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        $this->assertTrue($this->cache->has(['id' => 7]));

        $this->assertFalse($this->cache->has(['id' => 8]));

        $cacheElement = $this->cache->get(['id' => 7]);

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);
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
