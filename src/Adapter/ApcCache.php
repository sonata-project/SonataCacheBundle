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
     * @param string   $token   A token to clear the related cache
     * @param string   $prefix  A prefix to avoid clash between instances
     * @param string[] $servers An array of servers
     * @param array    $timeout An array of timeout options
     */
    public function __construct(
        RouterInterface $router,
        string $token,
        string $prefix,
        array $servers,
        array $timeout = []
    ) {
        parent::__construct('', $prefix, $servers, $timeout);

        $this->router = $router;
        $this->token = $token;
    }

    /**
     * @throws AccessDeniedHttpException
     */
    public function cacheAction(string $token): Response
    {
        if ($this->token === $token) {
            if (\function_exists('opcache_reset')) {
                opcache_reset();
            }

            if (\extension_loaded('apcu') && ini_get('apcu.enabled')) {
                apcu_clear_cache();
            }

            return new Response('ok', 200, [
                'Cache-Control' => 'no-cache, must-revalidate',
                'Content-Length' => 2, // to prevent chunked transfer encoding
            ]);
        }

        throw new AccessDeniedHttpException('Invalid token.');
    }

    protected function getUrl(): ?string
    {
        return $this->router->generate('sonata_cache_apc', ['token' => $this->token]);
    }
}
