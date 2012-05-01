<?php


/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\Cache;

use Sonata\CacheBundle\Adapter\MemcacheCache;
use Symfony\Component\Routing\RouterInterface;
use Sonata\CacheBundle\Cache\CacheElement;

class MemcacheCacheTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        if (!class_exists('\Memcache', true)) {
            $this->markTestSkipped('Memcache is not installed');
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        // setup the default timeout (avoid max execution time)
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0));

        $result = @socket_connect($socket, '127.0.0.1', 11211);

        if (!$result) {
            $this->markTestSkipped('Memcache is not running');
        }

        socket_close($socket);
    }

    public function testInitCache()
    {
        $cache = new MemcacheCache('sonata_cache_test', array(
            array('host' => '127.0.0.1', 'port' => 11211, 'weight' => 100, 'persistent' => false)
        ));

        $cache->set(array('id' => 7), 'data');
        $cacheElement = $cache->set(array('id' => 42), 'data');

        $this->assertInstanceOf('Sonata\CacheBundle\Cache\CacheElement', $cacheElement);

        $this->assertTrue($cache->has(array('id' => 7)));

        $cache->flush(array('id' => 42));

        $this->assertFalse($cache->has(array('id' => 42)));


        $cacheElement = $cache->get(array('id' => 7));

        $this->assertInstanceOf('Sonata\CacheBundle\Cache\CacheElement', $cacheElement);

        $cache->flushAll();

        $this->assertFalse($cache->has(array('id' => 7)));
    }
}