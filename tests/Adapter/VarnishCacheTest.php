<?php

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
use Sonata\CacheBundle\Adapter\VarnishCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class VarnishCacheTest extends TestCase
{
    public function testInitCache()
    {
        $router = $this->createMock('Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())->method('generate')->will($this->returnValue('https://sonata-project.org/cache/esi/TOKEN?controller=asdsad'));

        $resolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $cache = new VarnishCache('token', [], $router, 'ban', $resolver);

        $this->assertTrue($cache->flush([]));
        $this->assertTrue($cache->flushAll());

        $cacheElement = $cache->set(['id' => 7], 'data');

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        $this->assertTrue($cache->has(['id' => 7]));

        $cacheElement = $cache->get(['id' => 7, 'controller' => 'foo.service::runAction', 'parameters' => []]);

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        $this->assertEquals('<esi:include src="https://sonata-project.org/cache/esi/TOKEN?controller=asdsad"/>', $cacheElement->getData()->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testActionInvalidToken()
    {
        $router = $this->createMock('Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())->method('generate')->will($this->returnValue('http://sonata-project.orf/cache/esi/TOKEN?controller=asdsad'));

        $resolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $request = Request::create('cache/esi/TOKEN?controller=asdsad', 'get', [
            'token' => 'wrong',
        ]);

        $cache = new VarnishCache('token', [], $router, 'ban', $resolver);
        $cache->cacheAction($request);
    }

    public function testValidToken()
    {
        $router = $this->createMock('Symfony\Component\Routing\RouterInterface');

        $resolver = $this->createMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
        $resolver->expects($this->any())->method('getController')->will($this->returnValue(function () {
            return new Response();
        }));
        $resolver->expects($this->any())->method('getArguments')->will($this->returnValue([]));

        $request = Request::create('cache/esi/TOKEN', 'get', [
            'token' => '44befdbd93f304ea693023aa6587729bed76a206ecdacfd9bbd9b43fcf2e1664',
            'parameters' => [
                'controller' => 'asfsat',
                'parameters' => [],
            ],
        ]);

        $cache = new VarnishCache('token', [], $router, 'ban', $resolver);
        $cache->cacheAction($request);
    }

    public function testRunCommand()
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'sonata-cache');

        $cache = new VarnishCache(
            'token',
            [
                sprintf("echo \"varnishadm -T 10.4.1.62:6082 -S /etc/varnish/secret {{ COMMAND }} '{{ EXPRESSION }}'\" >> %s", $tmpFile),
                sprintf("echo \"varnishadm -T 10.4.1.66:6082 -S /etc/varnish/secret {{ COMMAND }} '{{ EXPRESSION }}'\" >> %s", $tmpFile),
            ],
            $this->createMock('Symfony\Component\Routing\RouterInterface'),
            'ban'
        );

        $method = new \ReflectionMethod($cache, 'runCommand');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($cache, 'ban', 'req.url ~ \'.*\''));

        $this->assertSame(<<<'CMD'
varnishadm -T 10.4.1.62:6082 -S /etc/varnish/secret ban 'req.url ~ '.*''
varnishadm -T 10.4.1.66:6082 -S /etc/varnish/secret ban 'req.url ~ '.*''

CMD
        , file_get_contents($tmpFile));

        unlink($tmpFile);
    }
}
