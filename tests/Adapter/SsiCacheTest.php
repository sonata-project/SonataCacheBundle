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

namespace Sonata\CacheBundle\Tests\Adapter\Cache;

use PHPUnit\Framework\TestCase;
use Sonata\Cache\CacheElement;
use Sonata\CacheBundle\Adapter\SsiCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

class SsiCacheTest extends TestCase
{
    private $router;
    private $controllerResolver;
    private $argumentResolver;
    private $cache;

    protected function setUp(): void
    {
        $this->router = $this->createMock(RouterInterface::class);
        $this->controllerResolver = $this->createMock(ControllerResolverInterface::class);
        $this->argumentResolver = $this->createMock(ArgumentResolverInterface::class);

        $this->cache = new SsiCache(
            'token',
            $this->router,
            $this->controllerResolver,
            $this->argumentResolver
        );
    }

    public function testInitCache(): void
    {
        $this->router->expects($this->any())
            ->method('generate')
            ->willReturn('/cache/esi/TOKEN?controller=asdsad');

        $this->assertTrue($this->cache->flush([]));
        $this->assertTrue($this->cache->flushAll());

        $cacheElement = $this->cache->set(['id' => 7], 'data');

        $this->assertInstanceOf(CacheElement::class, $cacheElement);

        $this->assertTrue($this->cache->has(['id' => 7]));

        $cacheElement = $this->cache->get([
            'id' => 7,
            'controller' => 'foo.service::runAction',
            'parameters' => [],
        ]);

        $this->assertInstanceOf(CacheElement::class, $cacheElement);

        $this->assertSame(
            '<!--# include virtual="/cache/esi/TOKEN?controller=asdsad" -->',
            $cacheElement->getData()->getContent()
        );
    }

    public function testActionInvalidToken(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->router->expects($this->any())
            ->method('generate')
            ->willReturn(
                'http://sonata-project.orf/cache/esi/TOKEN?controller=asdsad'
            );

        $request = Request::create('cache/esi/TOKEN?controller=asdsad', 'get', [
            'token' => 'wrong',
        ]);

        $this->cache->cacheAction($request);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testValidToken(): void
    {
        $this->controllerResolver->expects($this->any())
            ->method('getController')
            ->willReturn(static function () {
                return new Response();
            });

        $this->argumentResolver->expects($this->any())
            ->method('getArguments')
            ->willReturn([]);

        $request = Request::create('cache/esi/TOKEN', 'get', [
            'token' => '44befdbd93f304ea693023aa6587729bed76a206ecdacfd9bbd9b43fcf2e1664',
            'parameters' => [
                'controller' => 'asfsat',
                'parameters' => [],
            ],
        ]);

        $this->cache->cacheAction($request);
    }
}
