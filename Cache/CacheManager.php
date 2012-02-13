<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Cache;

use Sonata\CacheBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Cache\CacheElement;
use Sonata\CacheBundle\Invalidation\InvalidationInterface;
use Sonata\CacheBundle\Invalidation\Recorder;

class CacheManager implements CacheManagerInterface
{
    protected $cacheInvalidation;

    protected $logger;

    protected $cacheServices = array();

    protected $recorder;

    /**
     * @param \Sonata\CacheBundle\Invalidation\InvalidationInterface $cacheInvalidation
     * @param array $cacheServices
     */
    public function __construct(InvalidationInterface $cacheInvalidation, array $cacheServices)
    {
        $this->cacheInvalidation  = $cacheInvalidation;
        $this->cacheServices      = $cacheServices;
    }

    /**
     * {@inheritdoc}
     */
    public function addCacheService($name, CacheInterface $cacheManager)
    {
        $this->cacheServices[$name] = $cacheManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheService($name)
    {
        if (!$this->hasCacheService($name)) {
            throw new \RuntimeException(sprintf('The cache service %s does not exist.',$name));
        }

        return $this->cacheServices[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheServices()
    {
        return $this->cacheServices;
    }

    /**
     * {@inheritdoc}
     */
    public function hasCacheService($id)
    {
        return isset($this->cacheServices[$id]) ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(array $keys)
    {
        $this->cacheInvalidation->invalidate($this->getCacheServices(), $keys);
    }

    /**
     * {@inheritdoc}
     */
    public function setRecorder(Recorder $recorder)
    {
        $this->recorder = $recorder;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecorder()
    {
        return $this->recorder;
    }
}
