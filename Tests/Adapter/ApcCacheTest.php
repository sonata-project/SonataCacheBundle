<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\Adapter;

use Sonata\CacheBundle\Adapter\ApcCache;

class ApcCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var ApcCache
     */
    private $cache;

    public function setUp()
    {
        if (!function_exists('apc_store')) {
            $this->markTestSkipped('APC is not installed');
        }

        if (ini_get('apc.enable_cli') == 0) {
            $this->markTestSkipped('APC is not enabled in cli, please add apc.enable_cli=On into the apc.ini file');
        }

        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->cache = new ApcCache($this->router, 'token', 'prefix_', array());
    }

    public function testInitCache()
    {
        $this->assertTrue($this->cache->flush(array()));
        $this->assertTrue($this->cache->flushAll());

        $cacheElement = $this->cache->set(array('id' => 7), 'data');

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        $this->assertTrue($this->cache->has(array('id' => 7)));

        $this->assertFalse($this->cache->has(array('id' => 8)));

        $cacheElement = $this->cache->get(array('id' => 7));

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);
    }

    public function testGetUrl()
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('sonata_cache_apc'), $this->equalTo(array('token' => 'token')))
            ->will($this->returnValue('/sonata/cache/apc/token'));

        $method = new \ReflectionMethod($this->cache, 'getUrl');
        $method->setAccessible(true);

        $this->assertEquals('/sonata/cache/apc/token', $method->invoke($this->cache));
    }
}
