<?php


/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\Cache;

use Sonata\CacheBundle\Adapter\EsiCache;
use Symfony\Component\Routing\RouterInterface;
use Sonata\CacheBundle\Cache\CacheElement;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EsiCacheTest extends \PHPUnit_Framework_TestCase
{

    public function testInitCache()
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())->method('generate')->will($this->returnValue('http://sonata-project.org/cache/esi/TOKEN?controller=asdsad'));

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $cache = new EsiCache('token', array(), $router, $resolver);

        $this->assertTrue($cache->flush(array()));
        $this->assertTrue($cache->flushAll());

        $cacheElement = $cache->set(array('id' => 7), 'data');

        $this->assertInstanceOf('Sonata\CacheBundle\Cache\CacheElement', $cacheElement);

        $this->assertTrue($cache->has(array('id' => 7)));

        $cacheElement = $cache->get(array('id' => 7, 'controller' => 'foo.service::runAction', 'parameters' => array()));

        $this->assertInstanceOf('Sonata\CacheBundle\Cache\CacheElement', $cacheElement);

        $this->assertEquals('<esi:include src="http://sonata-project.org/cache/esi/TOKEN?controller=asdsad"/>', $cacheElement->getData()->getContent());
    }

    /**
     * @expectedException \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
     */
    public function testActionInvalidToken()
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $router->expects($this->any())->method('generate')->will($this->returnValue('http://sonata-project.orf/cache/esi/TOKEN?controller=asdsad'));

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');

        $request = Request::create('cache/esi/TOKEN?controller=asdsad', 'get', array(
            'token' => 'wrong'
        ));

        $cache = new EsiCache('token', array(), $router, $resolver);
        $cache->cacheAction($request);
    }

    public function testValidToken()
    {
        $router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $resolver = $this->getMock('Symfony\Component\HttpKernel\Controller\ControllerResolverInterface');
        $resolver->expects($this->any())->method('getController')->will($this->returnValue(function() { return new Response(); }));
        $resolver->expects($this->any())->method('getArguments')->will($this->returnValue(array()));


        $request = Request::create('cache/esi/TOKEN', 'get', array(
            'token' => '44befdbd93f304ea693023aa6587729bed76a206ecdacfd9bbd9b43fcf2e1664',
            'parameters' => array(
                'controller' => 'asfsat',
                'parameters' => array()
            )
        ));

        $cache = new EsiCache('token', array(), $router, $resolver);
        $cache->cacheAction($request);
    }
}