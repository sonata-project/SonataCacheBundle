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
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
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

    private $eventDispatcher;

    /**
     * Sets up cache adapter.
     */
    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->filesystem = $this->createMock(Filesystem::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

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
            ],
            $this->eventDispatcher
        );
    }

    public function testInitCache(): void
    {
        static::assertTrue($this->cache->flush([]));
        static::assertTrue($this->cache->flushAll());

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
        $eventSubscriber = $this->createMock(EventSubscriberInterface::class);
        $listener = new class() {
            public function onTerminate()
            {
            }
        };
        $listeners = ['console.terminate' => [
            [
                $eventSubscriber,
                'onCommandTerminate',
            ],
            [
                $listener,
                'onTerminate',
            ],
        ]];

        // Given
        $this->filesystem->expects(static::once())->method('exists')->willReturn(true);
        $this->filesystem->expects(static::once())->method('remove');
        $this->eventDispatcher->expects(static::once())->method('getListeners')->willReturn($listeners);
        $this->eventDispatcher->expects(static::once())->method('removeSubscriber')->with($eventSubscriber);
        $this->eventDispatcher->expects(static::once())->method('removeListener')->with('console.terminate', [
            $listener,
            'onTerminate',
        ]);

        // When
        $response = $this->cache->cacheAction('token', 'translations');

        // Then
        static::assertInstanceOf(Response::class, $response);

        static::assertSame(200, $response->getStatusCode(), 'Response should be 200');
        static::assertSame('ok', $response->getContent(), 'Response should return "OK"');

        static::assertSame('2', $response->headers->get('Content-Length'));
        static::assertSame('must-revalidate, no-cache, private', $response->headers->get('Cache-Control'));
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
            ],
            $this->eventDispatcher
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
            ],
            $this->eventDispatcher
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
                ->setFunction(static function (): void {
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
            ],
            $this->eventDispatcher
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
                ->setFunction(static function (): void {
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
     * @group legacy
     * @expectedDeprecation Passing no 9th argument to Sonata\CacheBundle\Adapter\SymfonyCache is deprecated since version sonata-project/cache-bundle 3.x and will be mandatory in 4.0.
     * Pass Symfony\Component\EventDispatcher\EventDispatcherInterface as 9th argument.
     */
    public function testCacheActionWithoutEventDispatcher(): void
    {
        $cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            ['all', 'translations'],
            [],
            []
        );

        $cache->cacheAction('token', 'translations');
    }
}
