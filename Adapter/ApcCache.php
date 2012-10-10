<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Adapter;

use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\RouterInterface;

use Sonata\CacheBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Cache\CacheElement;

class ApcCache implements CacheInterface
{
    protected $servers;

    protected $prefix;

    protected $collection;

    protected $router;

    /**
     * @param \Symfony\Component\Routing\RouterInterface $router
     * @param string $token
     * @param string $prefix
     * @param array $servers
     */
    public function __construct(RouterInterface $router, $token, $prefix, array $servers)
    {
        $this->token   = $token;
        $this->prefix  = $prefix;
        $this->servers = $servers;
        $this->router  = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        $result = true;
        foreach ($this->servers as $server) {
            if (count(explode('.', $server['ip']) == 3)) {
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            } else {
                $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
            }

            // generate the raw http request
            $command = sprintf("GET %s HTTP/1.1\r\n", $this->router->generate('sonata_cache_apc', array('token' => $this->token)));
            $command .= sprintf("Host: %s\r\n", $server['domain']);
            if ($server['basic']) {
                $command .= sprintf("Authorization: Basic %s\r\n", $server['basic']);
            }
            $command .= "Connection: Close\r\n\r\n";

            // setup the default timeout (avoid max execution time)
            socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 2, 'usec' => 0));
            socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 2, 'usec' => 0));

            socket_connect($socket, $server['ip'], $server['port']);

            socket_write($socket, $command);

            $content = socket_read($socket, 1024);

            if ($result) {
                $result = substr($content, -2) == 'ok' ? true : false;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array())
    {
        return $this->flushAll();
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        return apc_exists($this->computeCacheKeys($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array())
    {
        $cacheElement = new CacheElement($keys, $data, $ttl);

        $result = apc_store(
            $this->computeCacheKeys($keys),
            $cacheElement,
            $cacheElement->getTtl()
        );

        return $cacheElement;
    }

    /**
     * @param CacheElement $cacheElement
     * @return string
     */
    private function computeCacheKeys($keys)
    {
        ksort($keys);

        return md5($this->prefix.serialize($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        return apc_fetch($this->computeCacheKeys($keys));
    }

    /**
     * @throws \Symfony\Component\Security\Core\Exception\AccessDeniedException
     * @param $token
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cacheAction($token)
    {
        if ($this->token == $token) {
            apc_clear_cache('user');

            return new Response('ok', 200, array(
                'Cache-Control' => 'no-cache, must-revalidate'
            ));
        }

        throw new AccessDeniedException('invalid token');
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}