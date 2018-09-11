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

use Sonata\Cache\CacheAdapterInterface;
use Sonata\Cache\CacheElementInterface;
use Sonata\Cache\Exception\UnsupportedException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\RouterInterface;

/**
 * Handles Symfony cache.
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
     * @var string[]
     */
    protected $types;

    /**
     * @var bool
     */
    protected $phpCodeCacheEnabled;

    /**
     * @var array
     */
    protected $servers;

    /**
     * @var array
     */
    protected $timeouts;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(
        RouterInterface $router,
        Filesystem $filesystem,
        string $cacheDir,
        string $token,
        bool $phpCodeCacheEnabled,
        array $types,
        array $servers,
        array $timeouts
    ) {
        $this->router = $router;
        $this->filesystem = $filesystem;
        $this->cacheDir = $cacheDir;
        $this->token = $token;
        $this->types = $types;
        $this->phpCodeCacheEnabled = $phpCodeCacheEnabled;
        $this->servers = $servers;
        $this->timeouts = $timeouts;
    }

    public function flushAll(): bool
    {
        return $this->flush(['all']);
    }

    /**
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     */
    public function flush(array $keys = ['all']): bool
    {
        $result = true;

        foreach ($this->servers as $server) {
            foreach ($keys as $type) {
                $ip = $server['ip'];

                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $socket = socket_create(AF_INET6, SOCK_STREAM, SOL_TCP);
                } else {
                    throw new \InvalidArgumentException(sprintf('"%s" is not a valid ip address', $ip));
                }

                // generate the raw http request
                $command = sprintf("GET %s HTTP/1.1\r\n", $this->getUrl($type));
                $command .= sprintf("Host: %s\r\n", $server['domain']);

                if ($server['basic']) {
                    $command .= sprintf("Authorization: Basic %s\r\n", $server['basic']);
                }

                $command .= "Connection: Close\r\n\r\n";

                // setup the default timeout (avoid max execution time)
                socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, $this->timeouts['SND']);
                socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, $this->timeouts['RCV']);

                socket_connect($socket, $server['ip'], $server['port']);
                socket_write($socket, $command);

                $content = '';

                do {
                    $buffer = socket_read($socket, 1024);
                    $content .= $buffer;
                } while (!empty($buffer));

                if ($result) {
                    $result = 'ok' === substr($content, -2);
                } else {
                    throw new \UnexpectedValueException(sprintf(
                        'Server answered with "%s"',
                        $content
                    ));
                }
            }
        }

        return $result;
    }

    /**
     * @throws AccessDeniedHttpException
     * @throws \RuntimeException
     */
    public function cacheAction(string $token, string $type): Response
    {
        if ($this->token != $token) {
            throw new AccessDeniedHttpException('Invalid token.');
        }

        if (!\in_array($type, $this->types)) {
            throw new \RuntimeException(
                sprintf('Type "%s" is not defined, allowed types are: "%s"', $type, implode(', ', $this->types))
            );
        }

        $path = 'all' === $type ? $this->cacheDir : sprintf('%s/%s', $this->cacheDir, $type);

        if ($this->filesystem->exists($path)) {
            $movedPath = $path.'_old_'.uniqid();

            $this->filesystem->rename($path, $movedPath);
            $this->filesystem->remove($movedPath);

            $this->clearPHPCodeCache();
        }

        return new Response('ok', 200, [
            'Cache-Control' => 'no-cache, must-revalidate',
            'Content-Length' => 2, // to prevent chunked transfer encoding
        ]);
    }

    /**
     * @throws UnsupportedException
     */
    public function has(array $keys): bool
    {
        throw new UnsupportedException('SymfonyCache has() method does not exist.');
    }

    /**
     * @throws UnsupportedException
     */
    public function set(array $keys, $data, int $ttl = 84600, array $contextualKeys = []): CacheElementInterface
    {
        throw new UnsupportedException('SymfonyCache set() method does not exist.');
    }

    /**
     * @throws UnsupportedException
     */
    public function get(array $keys): CacheElementInterface
    {
        throw new UnsupportedException('SymfonyCache get() method does not exist.');
    }

    public function isContextual(): bool
    {
        return false;
    }

    protected function getUrl(string $type): ?string
    {
        return $this->router->generate('sonata_cache_symfony', [
            'token' => $this->token,
            'type' => $type,
        ]);
    }

    protected function clearPHPCodeCache(): void
    {
        if (!$this->phpCodeCacheEnabled) {
            return;
        }

        if (\function_exists('opcache_reset')) {
            opcache_reset();
        }
    }
}
