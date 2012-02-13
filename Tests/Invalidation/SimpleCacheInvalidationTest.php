<?php


/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\Cache\Invalidation;

use Sonata\CacheBundle\Cache\CacheElement;
use Sonata\CacheBundle\Invalidation\SimpleCacheInvalidation;
use Sonata\CacheBundle\Cache\CacheInterface;

class SimpleCacheInvalidationTest_Cache
{}

class SimpleCacheInvalidationTest extends \PHPUnit_Framework_TestCase
{
    public function testInvalidate()
    {
        $cacheInvalidation = new SimpleCacheInvalidation;

        $cache = $this->getMock('Sonata\CacheBundle\Cache\CacheInterface');
        $cache->expects($this->exactly(1))->method('flush');

        $caches = array($cache);

        $this->assertTrue($cacheInvalidation->invalidate($caches, array('test' => 1)));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testWithoutLogger()
    {
        $cacheInvalidation = new SimpleCacheInvalidation;

        $cache = $this->getMock('Sonata\CacheBundle\Cache\CacheInterface');
        $cache->expects($this->exactly(1))->method('flush')->will($this->throwException(new \Exception));

        $caches = array($cache);

        $cacheInvalidation->invalidate($caches, array('page_id' => 1));
    }

    public function testWithLogger()
    {
        $logger = $this->getMock('Symfony\Component\HttpKernel\Log\LoggerInterface', array(), array(), '', false);
        $logger->expects($this->exactly(1))->method('info');
        $logger->expects($this->exactly(1))->method('alert');

        $cacheInvalidation = new SimpleCacheInvalidation($logger);

        $cache = $this->getMock('Sonata\CacheBundle\Cache\CacheInterface');
        $cache->expects($this->exactly(1))->method('flush')->will($this->throwException(new \Exception));

        $caches = array($cache);

        $cacheInvalidation->invalidate($caches, array('page_id' => 1));
    }

    /**
     * @expectedException RuntimeException
     */
    public function testInvalidCacheHandle()
    {
        $cacheInvalidation = new SimpleCacheInvalidation();

        $caches = array(new SimpleCacheInvalidationTest_Cache);

        $cacheInvalidation->invalidate($caches, array('page_id' => 1));
    }
}
