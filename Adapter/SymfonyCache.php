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

use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\Exception\UnsupportedException;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Handles Symfony cache
 *
 * @author Vincent Composieux <vincent.composieux@gmail.com>
 */
class SymfonyCache implements CacheAdapterInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var string
     */
    protected $cacheDir;

    /**
     * @var string
     */
    protected $token;

    /**
     * @var string
     */
    protected $types;

    /**
     * @var boolean
     */
    protected $phpCodeCacheEnabled;

    /**
     * @var array
     */
    protected $servers;

    /**
     * Constructor
     *
     * @param RouterInterface $router              A router instance
     * @param Filesystem      $filesystem          A Symfony Filesystem component instance
     * @param string          $cacheDir            A Symfony cache directory
     * @param string          $token               A token to clear the related cache
     * @param boolean         $phpCodeCacheEnabled If true, will clear APC or PHP OPcache code cache
     * @param array           $types               A cache types array
     * @param array           $servers             An array of servers
     */
    public function __construct(RouterInterface $router, Filesystem $filesystem, $cacheDir, $token, $phpCodeCacheEnabled, array $types, array $servers)
    {
        $this->router              = $router;
        $this->filesystem          = $filesystem;
        $this->cacheDir            = $cacheDir;
        $this->token               = $token;
        $this->types               = $types;
        $this->phpCodeCacheEnabled = $phpCodeCacheEnabled;
        $this->servers             = $servers;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->flush(array('all'));
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array('all'))
    {
        $result = true;

        foreach ($this->servers as $server) {
            foreach ($keys as $type) {
                if (count(explode('.', $server['ip']) == 3)) {
                    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                } else {
                    $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
                }

                // generate the raw http request
                $command = sprintf("GET %s HTTP/1.1\r\n", $this->getUrl($type));
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

                $content = '';

                do {
                    $buffer = socket_read($socket, 1024);
                    $content .= $buffer;
                } while (!empty($buffer));

                if ($result) {
                    $result = substr($content, -2) == 'ok';
                } else {
                    return $content;
                }
            }
        }

        return $result;
    }

    /**
     * Symfony cache action
     *
     * @param string $token A Sonata symfony cache token
     * @param string $type  A cache type to invalidate (doctrine, translations, twig, ...)
     *
     * @return Response
     *
     * @throws AccessDeniedHttpException if token is invalid
     * @throws \RuntimeException if specified type is not in allowed types list
     */
    public function cacheAction($token, $type)
    {
        if ($this->token != $token) {
            throw new AccessDeniedHttpException('Invalid token');
        }

        if (!in_array($type, $this->types)) {
            throw new \RuntimeException(
                sprintf('Type "%s" is not defined, allowed types are: "%s"', $type, implode(', ', $this->types))
            );
        }

        $path = 'all' == $type ? $this->cacheDir : sprintf('%s/%s', $this->cacheDir, $type);

        if ($this->filesystem->exists($path)) {
            $movedPath = $path . '_old_' . uniqid();

            $this->filesystem->rename($path, $movedPath);
            $this->filesystem->remove($movedPath);

            $this->clearPHPCodeCache();
        }

        return new Response('ok', 200, array(
            'Cache-Control'  => 'no-cache, must-revalidate',
            'Content-Length' => 2, // to prevent chunked transfer encoding
        ));
    }

    /**
     * Returns URL with given token used for cache invalidation
     *
     * @param string $type
     *
     * @return string
     */
    protected function getUrl($type)
    {
        return $this->router->generate('sonata_cache_symfony', array(
            'token' => $this->token,
            'type'  => $type
        ));
    }

    /**
     * Clears code cache with:
     *
     * PHP < 5.5.0: APC
     * PHP >= 5.5.0: PHP OPcache
     *
     * @return void
     */
    protected function clearPHPCodeCache()
    {
        if (!$this->phpCodeCacheEnabled) {
            return;
        }

        if (version_compare(PHP_VERSION, '5.5.0', '>=') && function_exists('opcache_reset')) {
            opcache_reset();
        } else if (function_exists('apc_fetch')) {
            apc_clear_cache();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        throw new UnsupportedException('Symfony cache has() method does not exists');
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array())
    {
        throw new UnsupportedException('Symfony cache set() method does not exists');
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        throw new UnsupportedException('Symfony cache get() method does not exists');
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}
