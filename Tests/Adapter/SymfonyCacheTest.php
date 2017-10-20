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
use Sonata\CacheBundle\Adapter\SymfonyCache;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;
use phpmock\MockBuilder;

/**
 * Class SymfonyCacheTest.
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class SymfonyCacheTest extends TestCase
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
     * Sets up cache adapter.
     */
    protected function setUp()
    {
        $this->router = $this->createMock('Symfony\Component\Routing\RouterInterface');
        $this->filesystem = $this->createMock('Symfony\Component\Filesystem\Filesystem');

        $this->cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            ['all', 'translations'],
            [],
            []
        );
    }

    /**
     * Tests cache initialization.
     */
    public function testInitCache()
    {
        $this->assertTrue($this->cache->flush([]));
        $this->assertTrue($this->cache->flushAll());

        $this->setExpectedException('Sonata\Cache\Exception\UnsupportedException', 'Symfony cache set() method does not exists');
        $this->cache->set(['id' => 5], 'data');

        $this->setExpectedException('Sonata\Cache\Exception\UnsupportedException', 'Symfony cache get() method does not exists');
        $this->cache->get(['id' => 5]);

        $this->setExpectedException('Sonata\Cache\Exception\UnsupportedException', 'Symfony cache has() method does not exists');
        $this->cache->has(['id' => 5]);
    }

    /**
     * Tests cacheAction() method.
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
     * Tests cacheAction() method with an invalid token.
     */
    public function testCacheActionWithInvalidToken()
    {
        // Given
        // When - Then expect exception
        $this->setExpectedException('Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException');

        $this->cache->cacheAction('invalid-token', 'type');
    }

    /**
     * Tests cacheAction() method with an invalid cache type.
     */
    public function testCacheActionWithInvalidType()
    {
        // Given
        // When - Then expect exception
        $this->setExpectedException('\RuntimeException', 'Type "invalid-type" is not defined, allowed types are: "all, translations"');

        $this->cache->cacheAction('token', 'invalid-type');
    }

    /**
     * Asserts the flush method throws an exception if the IP version of the server cannot be detected.
     */
    public function testFlushThrowsExceptionWithWrongIP()
    {
        $cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            ['all', 'translations'],
            [
                ['ip' => 'wrong ip'],
            ],
            []
        );

        $this->setExpectedException('\InvalidArgumentException', '"wrong ip" is not a valid ip address');

        $cache->flush();
    }

    /**
     * Tests the flush method with IPv4.
     */
    public function testFlushWithIPv4()
    {
        $cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            ['all', 'translations'],
            [
                ['ip' => '213.186.35.9', 'domain' => 'www.example.com', 'basic' => false, 'port' => 80],
            ],
            [
                'RCV' => ['sec' => 2, 'usec' => 0],
                'SND' => ['sec' => 2, 'usec' => 0],
            ]
        );

        $mocks = [];

        $builder = new MockBuilder();
        $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
            ->setName('socket_create')
            ->setFunction(function () {
                $this->assertSame([AF_INET, SOCK_STREAM, SOL_TCP], func_get_args());
            })
            ->build();
        $mock->enable();

        $mocks[] = $mock;

        foreach (['socket_set_option', 'socket_connect', 'socket_write', 'socket_read'] as $function) {
            $builder = new MockBuilder();
            $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
                ->setName($function)
                ->setFunction(function () {
                })
                ->build();
            $mock->enable();

            $mocks[] = $mock;
        }

        $cache->flush();

        foreach ($mocks as $mock) {
            $mock->disable();
        }
    }

    /**
     * Tests the flush method with IPv6.
     */
    public function testFlushWithIPv6()
    {
        $cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            ['all', 'translations'],
            [
                ['ip' => '2001:41d0:1:209:FF:FF:FF:FF', 'domain' => 'www.example.com', 'basic' => false, 'port' => 80],
            ],
            [
                'RCV' => ['sec' => 2, 'usec' => 0],
                'SND' => ['sec' => 2, 'usec' => 0],
            ]
        );

        $mocks = [];

        $builder = new MockBuilder();
        $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
            ->setName('socket_create')
            ->setFunction(function () {
                $this->assertSame([AF_INET6, SOCK_STREAM, SOL_TCP], func_get_args());
            })
            ->build();
        $mock->enable();

        $mocks[] = $mock;

        foreach (['socket_set_option', 'socket_connect', 'socket_write', 'socket_read'] as $function) {
            $builder = new MockBuilder();
            $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
                ->setName($function)
                ->setFunction(function () {
                })
                ->build();
            $mock->enable();

            $mocks[] = $mock;
        }

        $cache->flush();

        foreach ($mocks as $mock) {
            $mock->disable();
        }
    }
}
