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
use Sonata\CacheBundle\Adapter\VarnishCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

class VarnishCacheTest extends TestCase
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

        $this->cache = new VarnishCache(
            'token',
            [],
            $this->router,
            'ban',
            $this->controllerResolver,
            $this->argumentResolver
        );
    }

    public function testInitCache(): void
    {
        $this->router->expects(static::any())
            ->method('generate')
            ->willReturn(
                'https://sonata-project.org/cache/esi/TOKEN?controller=asdsad'
            );

        static::assertTrue($this->cache->flush([]));
        static::assertTrue($this->cache->flushAll());

        $cacheElement = $this->cache->set(['id' => 7], 'data');

        static::assertInstanceOf(CacheElement::class, $cacheElement);

        static::assertTrue($this->cache->has(['id' => 7]));

        $cacheElement = $this->cache->get([
            'id' => 7,
            'controller' => 'foo.service::runAction',
            'parameters' => [],
        ]);

        static::assertInstanceOf(CacheElement::class, $cacheElement);

        static::assertSame(
            '<esi:include src="https://sonata-project.org/cache/esi/TOKEN?controller=asdsad"/>',
            $cacheElement->getData()->getContent()
        );
    }

    public function testActionInvalidToken(): void
    {
        $this->expectException(AccessDeniedHttpException::class);

        $this->router->expects(static::any())
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
        $this->controllerResolver->expects(static::any())
            ->method('getController')
            ->willReturn(static function () {
                return new Response();
            });

        $this->argumentResolver->expects(static::any())
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

    public function testRunCommand(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sonata-cache');

        $cache = new VarnishCache(
            'token',
            [
                sprintf("echo \"varnishadm -T 10.4.1.62:6082 -S /etc/varnish/secret {{ COMMAND }} '{{ EXPRESSION }}'\" >> %s", $tmpFile),
                sprintf("echo \"varnishadm -T 10.4.1.66:6082 -S /etc/varnish/secret {{ COMMAND }} '{{ EXPRESSION }}'\" >> %s", $tmpFile),
            ],
            $this->router,
            'ban',
            $this->controllerResolver,
            $this->argumentResolver
        );

        $method = new \ReflectionMethod($cache, 'runCommand');
        $method->setAccessible(true);

        static::assertTrue($method->invoke($cache, 'ban', 'req.url ~ \'.*\''));

        static::assertSame(<<<'CMD'
varnishadm -T 10.4.1.62:6082 -S /etc/varnish/secret ban 'req.url ~ '.*''
varnishadm -T 10.4.1.66:6082 -S /etc/varnish/secret ban 'req.url ~ '.*''

CMD
        , file_get_contents($tmpFile));

        unlink($tmpFile);
    }
}
