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

use phpmock\MockBuilder;
use PHPUnit\Framework\TestCase;
use Sonata\Cache\Exception\UnsupportedException;
use Sonata\CacheBundle\Adapter\SymfonyCache;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class SymfonyCacheTest extends TestCase
{
    /**
     * @var SymfonyCache
     */
    protected $cache;

    protected $router;

    protected $filesystem;

    /**
     * Sets up cache adapter.
     */
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);

        $this->cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            ['all', 'translations'],
            [],
            [
                'RCV' => ['sec' => 2, 'usec' => 0],
                'SND' => ['sec' => 2, 'usec' => 0],
            ]
        );
    }

    public function testInitCache(): void
    {
        $this->assertTrue($this->cache->flush([]));
        $this->assertTrue($this->cache->flushAll());

        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('SymfonyCache set() method does not exist.');
        $this->cache->set(['id' => 5], 'data');

        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('SymfonyCache get() method does not exist.');
        $this->cache->get(['id' => 5]);

        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('SymfonyCache has() method does not exist.');
        $this->cache->has(['id' => 5]);
    }

    public function testCacheAction(): void
    {
        // Given
        $this->filesystem->expects($this->once())->method('exists')->will($this->returnValue(true));
        $this->filesystem->expects($this->once())->method('remove');

        // When
        $response = $this->cache->cacheAction('token', 'translations');

        // Then
        $this->assertInstanceOf(Response::class, $response);

        $this->assertEquals(200, $response->getStatusCode(), 'Response should be 200');
        $this->assertEquals('ok', $response->getContent(), 'Response should return "OK"');

        $this->assertEquals(2, $response->headers->get('Content-Length'));
        $this->assertEquals('must-revalidate, no-cache, private', $response->headers->get('Cache-Control'));
    }

    public function testCacheActionWithInvalidToken(): void
    {
        // Given
        // When - Then expect exception
        $this->expectException(AccessDeniedHttpException::class);

        $this->cache->cacheAction('invalid-token', 'type');
    }

    public function testCacheActionWithInvalidType(): void
    {
        // Given
        // When - Then expect exception
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Type "invalid-type" is not defined, allowed types are: "all, translations"');

        $this->cache->cacheAction('token', 'invalid-type');
    }

    public function testFlushThrowsExceptionWithWrongIP(): void
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
            [
                'RCV' => ['sec' => 2, 'usec' => 0],
                'SND' => ['sec' => 2, 'usec' => 0],
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('"wrong ip" is not a valid ip address');

        $cache->flush();
    }

    public function testFlushWithIPv4(): void
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
            ->setFunction(function (): void {
                $this->assertSame([AF_INET, SOCK_STREAM, SOL_TCP], \func_get_args());
            })
            ->build();
        $mock->enable();

        $mocks[] = $mock;

        foreach (['socket_set_option', 'socket_connect', 'socket_write', 'socket_read'] as $function) {
            $builder = new MockBuilder();
            $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
                ->setName($function)
                ->setFunction(function (): void {
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
    public function testFlushWithIPv6(): void
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
            ->setFunction(function (): void {
                $this->assertSame([AF_INET6, SOCK_STREAM, SOL_TCP], \func_get_args());
            })
            ->build();
        $mock->enable();

        $mocks[] = $mock;

        foreach (['socket_set_option', 'socket_connect', 'socket_write', 'socket_read'] as $function) {
            $builder = new MockBuilder();
            $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
                ->setName($function)
                ->setFunction(function (): void {
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
