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

use Sonata\CacheBundle\Adapter\SymfonyCache;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class SymfonyCacheTest.
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
     * Sets up cache adapter.
     */
    protected function setUp()
    {
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->filesystem = $this->getMock('Symfony\Component\Filesystem\Filesystem');

        $this->cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            array('all', 'translations'),
            array(),
            array()
        );
    }

    /**
     * Tests cache initialization.
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
            array('all', 'translations'),
            array(
                array('ip' => 'wrong ip'),
            ),
            array()
        );

        $this->setExpectedException('\InvalidArgumentException', '"wrong ip" is not a valid ip address');

        $cache->flush();
    }

    /**
     * Tests the flush method with IPv4.
     */
    public function testFlushWithIPv4()
    {
        $mockBuilderClass = $this->getMockBuilderClass();

        $cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            array('all', 'translations'),
            array(
                array('ip' => '213.186.35.9', 'domain' => 'www.example.com', 'basic' => false, 'port' => 80),
            ),
            array(
                'RCV' => array('sec' => 2, 'usec' => 0),
                'SND' => array('sec' => 2, 'usec' => 0),
            )
        );

        // NEXT_MAJOR: while dropping old versions of php, remove this and simplify the closure below
        $that = $this;

        $mocks = array();

        $builder = new $mockBuilderClass();
        $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
            ->setName('socket_create')
            ->setFunction(function () use ($that) {
                $that->assertSame(array(AF_INET, SOCK_STREAM, SOL_TCP), func_get_args());
            })
            ->build();
        $mock->enable();

        $mocks[] = $mock;

        foreach (array('socket_set_option', 'socket_connect', 'socket_write', 'socket_read') as $function) {
            $builder = new $mockBuilderClass();
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
        $mockBuilderClass = $this->getMockBuilderClass();

        $cache = new SymfonyCache(
            $this->router,
            $this->filesystem,
            '/cache/dir',
            'token',
            false,
            array('all', 'translations'),
            array(
                array('ip' => '2001:41d0:1:209:FF:FF:FF:FF', 'domain' => 'www.example.com', 'basic' => false, 'port' => 80),
            ),
            array(
                'RCV' => array('sec' => 2, 'usec' => 0),
                'SND' => array('sec' => 2, 'usec' => 0),
            )
        );

        // NEXT_MAJOR: while dropping old versions of php, remove this and simplify the closure below
        $that = $this;

        $mocks = array();

        $builder = new $mockBuilderClass();
        $mock = $builder->setNamespace('Sonata\CacheBundle\Adapter')
            ->setName('socket_create')
            ->setFunction(function () use ($that) {
                $that->assertSame(array(AF_INET6, SOCK_STREAM, SOL_TCP), func_get_args());
            })
            ->build();
        $mock->enable();

        $mocks[] = $mock;

        foreach (array('socket_set_option', 'socket_connect', 'socket_write', 'socket_read') as $function) {
            $builder = new $mockBuilderClass();
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
     * Gets the mock builder class according to the lib version (ie the PHP version).
     * NEXT_MAJOR: while dropping old versions of php, restrict the library to version ^1.0 and simplify this.
     *
     * @return string
     */
    private function getMockBuilderClass()
    {
        if (class_exists('phpmock\MockBuilder')) {
            return 'phpmock\MockBuilder';
        } elseif (class_exists('malkusch\phpmock\MockBuilder')) {
            return 'malkusch\phpmock\MockBuilder';
        }

        $this->fail('Unable to find the MockBuilder class to mock built-in PHP functions');
    }
}
