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

use Sonata\CacheBundle\Adapter\OpCodeCache;

class OpCodeCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Symfony\Component\Routing\RouterInterface
     */
    private $router;

    /**
     * @var OpCodeCache
     */
    private $cache;

    public function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->cache = new OpCodeCache($this->router, 'token', 'prefix_', array(), array());
    }

    public function testInitCache()
    {
        $this->assertTrue($this->cache->flushAll());

        if ($this->cache->hasApc()) {
            $this->assertTrue($this->cache->flush(array()));

            $cacheElement = $this->cache->set(array('id' => 7), 'data');

            $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

            $this->assertTrue($this->cache->has(array('id' => 7)));

            $this->assertFalse($this->cache->has(array('id' => 8)));

            $cacheElement = $this->cache->get(array('id' => 7));

            $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);
        }
    }

    public function testGetUrl()
    {
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with($this->equalTo('sonata_cache_opcode'), $this->equalTo(array('token' => 'token')))
            ->will($this->returnValue('/sonata/cache/opcode/token'));

        $method = new \ReflectionMethod($this->cache, 'getUrl');
        $method->setAccessible(true);

        $this->assertEquals('/sonata/cache/opcode/token', $method->invoke($this->cache));
    }
}
