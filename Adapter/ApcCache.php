<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Adapter;

use Sonata\Cache\Adapter\Cache\ApcCache as BaseApcCache;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

class ApcCache extends BaseApcCache
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
     * Constructor.
     *
     * @param RouterInterface $router  A router instance
     * @param string          $token   A token to clear the related cache
     * @param string          $prefix  A prefix to avoid clash between instances
     * @param array           $servers An array of servers
     * @param array           $timeout An array of timeout options
     */
    public function __construct(RouterInterface $router, $token, $prefix, array $servers, array $timeout = array())
    {
        parent::__construct(null, $prefix, $servers, $timeout);

        $this->router = $router;
        $this->token = $token;
    }

    /**
     * Cache action.
     *
     * @param string $token A configured token
     *
     * @return Response
     *
     * @throws AccessDeniedHttpException
     */
    public function cacheAction($token)
    {
        if ($this->token == $token) {
            if (version_compare(PHP_VERSION, '5.5.0', '>=') && function_exists('opcache_reset')) {
                opcache_reset();
            }

            if (extension_loaded('apc') && ini_get('apc.enabled')) {
                apc_clear_cache('user');
                apc_clear_cache();
            }

            return new Response('ok', 200, array(
                'Cache-Control' => 'no-cache, must-revalidate',
                'Content-Length' => 2, // to prevent chunked transfer encoding
            ));
        }

        throw new AccessDeniedHttpException('invalid token');
    }

    /**
     * {@inheritdoc}
     */
    protected function getUrl()
    {
        return $this->router->generate('sonata_cache_apc', array('token' => $this->token));
    }
}
