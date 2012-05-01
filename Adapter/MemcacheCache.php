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

use Sonata\CacheBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Cache\CacheElement;

/**
 * Memcache Cache Adapter
 *
 * @author Andrej Hudec <pulzarraider@gmail.com>
 */
class MemcacheCache implements CacheInterface
{
    protected $servers;

    protected $prefix;

    protected $collection;

    /**
     * @param $prefix
     * @param array $servers
     */
    public function __construct($prefix, array $servers)
    {
        $this->prefix  = $prefix;
        $this->servers = $servers;
    }

    /**
     * {@inheritdoc}
     */
    public function flushAll()
    {
        return $this->getCollection()->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function flush(array $keys = array())
    {
        return $this->getCollection()->delete($this->computeCacheKeys($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function has(array $keys)
    {
        return $this->getCollection()->get($this->computeCacheKeys($keys)) !== false;
    }

    /**
     * {@inheritdoc}
     */
    private function getCollection()
    {
        if (!$this->collection) {
            $this->collection = new \Memcache();

            foreach ($this->servers as $server) {
                $this->collection->addServer($server['host'], $server['port'], $server['persistent'], $server['weight']);
            }
        }

        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function set(array $keys, $data, $ttl = 84600, array $contextualKeys = array())
    {
        $cacheElement = new CacheElement($keys, $data, $ttl);

        $this->getCollection()->set(
            $this->computeCacheKeys($keys),
            $cacheElement,
            false,
            time() + $cacheElement->getTtl()
        );

        return $cacheElement;
    }

    /**
     * {@inheritdoc}
     */
    private function computeCacheKeys(array $keys)
    {
        ksort($keys);

        return md5($this->prefix.serialize($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $keys)
    {
        return $this->getCollection()->get($this->computeCacheKeys($keys));
    }

    /**
     * {@inheritdoc}
     */
    public function isContextual()
    {
        return false;
    }
}