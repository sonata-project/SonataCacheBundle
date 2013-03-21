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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Routing\RouterInterface;

use Sonata\CacheBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Cache\CacheElement;

class ApcCache implements CacheInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $prefix;

    /**
     * @var array
     */
    protected $servers;

    /**
     * Constructor
     *
     * @param RouterInterface $router  A router instance
     * @param string          $token   A token to clear the related cache
     * @param string          $prefix  A prefix to avoid clash between instances
     * @param array           $servers An array of servers
     */
    public function __construct(RouterInterface $router, $token, $prefix, array $servers)
    {
        $this->router  = $router;
        $this->token   = $token;
        $this->prefix  = $prefix;
        $this->servers = $servers;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        $result = true;

        $uri = $this->router->generate('sonata_cache_apc', array('token' => $this->token));

        foreach ($this->servers as $server) {
            $options = array(
                'http' => array(
                    'method'  => 'GET',
                    'timeout' => 2,
                ),
            );

            if ($server['basic']) {
                $options['http']['header'] = sprintf('Authorization: Basic %s', $server['basic']);
            }

            $content = file_get_contents(sprintf('%s:%s%s', $server['domain'], $server['port'], $uri), false, stream_context_create($options));

            if ($result) {
                $result = false !== $content && 'ok' === substr($content, -2);
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
     * @param  CacheElement $cacheElement
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
     * Cache action
     *
     * @param string $token A configured token
     *
     * @return Response
     *
     * @throws AccessDeniedException
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
