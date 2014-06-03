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

use Sonata\CacheBundle\Adapter\SymfonyCache;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class SymfonyCacheTest
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class SymfonyCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SymfonyCache
     */
    protected $cache;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Sets up cache adapter
     */
    public function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');

        $this->cache = new SymfonyCache($this->router, $this->filesystem, '/cache/dir', 'token', false, array('all', 'translations'), array());
    }

    /**
     * Tests cache initialization
     */
    public function testInitCache()
    {
        $this->assertTrue($this->cache->flush(array()));
        $this->assertTrue($this->cache->flushAll());

        $this->setExpectedException('Sonata\Cache\Exception\UnsupportedException', 'Symfony cache set() method does not exists');
        $this->cache->set(array('id' => 5), 'data');

        $this->setExpectedException('Sonata\Cache\Exception\UnsupportedException', 'Symfony cache get() method does not exists');
        $this->cache->get(array('id' => 5));

        $this->setExpectedException('Sonata\Cache\Exception\UnsupportedException', 'Symfony cache has() method does not exists');
        $this->cache->has(array('id' => 5));
    }

    /**
     * Tests cacheAction() method
     */
    public function testCacheAction()
    {
        // Given
        $this->filesystem->expects($this->once())->method('exists')->will($this->returnValue(true));
        $this->filesystem->expects($this->once())->method('remove');

        // When
        $response = $this->cache->cacheAction('token', 'translations');

        // Then
        $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $response);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should be 200');
        $this->assertEquals('ok', $response->getContent(), 'Response should return "OK"');

        $this->assertEquals(2, $response->headers->get('Content-Length'));
        $this->assertEquals('must-revalidate, no-cache, private', $response->headers->get('Cache-Control'));
    }

    /**
     * Tests cacheAction() method with an invalid token
     */
    public function testCacheActionWithInvalidToken()
    {
        // Given
        // When - Then expect exception
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException');

        $this->cache->cacheAction('invalid-token', 'type');
    }

    /**
     * Tests cacheAction() method with an invalid cache type
     */
    public function testCacheActionWithInvalidType()
    {
        // Given
        // When - Then expect exception
        $this->setExpectedException('\RuntimeException', 'Type "invalid-type" is not defined, allowed types are: "all, translations"');

        $this->cache->cacheAction('token', 'invalid-type');
    }
}
