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

use Sonata\CacheBundle\Adapter\SsiCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SsiCacheTest extends \PHPUnit_Framework_TestCase
{
    public function testInitCache()
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())->method('generate')->will($this->returnValue('/cache/esi/TOKEN?controller=asdsad'));

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $cache = new SsiCache('token', $router, $resolver);

        $this->assertTrue($cache->flush([]));
        $this->assertTrue($cache->flushAll());

        $cacheElement = $cache->set(['id' => 7], 'data');

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        $this->assertTrue($cache->has(['id' => 7]));

        $cacheElement = $cache->get(['id' => 7, 'controller' => 'foo.service::runAction', 'parameters' => []]);

        $this->assertInstanceOf('Sonata\Cache\CacheElement', $cacheElement);

        $this->assertEquals('<!--# include virtual="/cache/esi/TOKEN?controller=asdsad" -->', $cacheElement->getData()->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testActionInvalidToken()
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())->method('generate')->will($this->returnValue('http://sonata-project.orf/cache/esi/TOKEN?controller=asdsad'));

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $request = Request::create('cache/esi/TOKEN?controller=asdsad', 'get', [
            'token' => 'wrong',
        ]);

        $cache = new SsiCache('token', $router, $resolver);
        $cache->cacheAction($request);
    }

    public function testValidToken()
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
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

        $cache = new SsiCache('token', $router, $resolver);
        $cache->cacheAction($request);
    }
}
