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

    protected $debug = false;

    protected $cacheServices = array();

    protected $recorder;

    public function __construct(InvalidationInterface $cacheInvalidation)
    {
        $this->cacheInvalidation  = $cacheInvalidation;
    }

    /**
     * @param $name
     * @param \Sonata\CacheBundle\Cache\CacheInterface $cacheManager
     * @return void
     */
    public function addCacheService($name, CacheInterface $cacheManager)
    {
        $this->cacheServices[$name] = $cacheManager;
    }

    /**
     * @throws \RuntimeException
     * @param $name
     * @return
     */
    public function getCacheService($name)
    {
        if (!$this->hasCacheService($name)) {
            throw new \RuntimeException(sprintf('The cache service %s does not exist.',$name));
        }

        return $this->cacheServices[$name];
    }

    /**
     * Returns related cache services
     *
     * @return array
     */
    public function getCacheServices()
    {
        return $this->cacheServices;
    }

    /**
     *
     * @param sring $id
     * @return boolean
     */
    public function hasCacheService($id)
    {
        return isset($this->cacheServices[$id]) ? true : false;
    }

    /**
     * @param \Sonata\CacheBundle\Cache\CacheElement $cacheElement
     * @return void
     */
    public function invalidate(CacheElement $cacheElement)
    {
        $this->cacheInvalidation->invalidate($this->getCacheServices(), $cacheElement);
    }

    /**
     * @param \Sonata\CacheBundle\Invalidation\Recorder $recorder
     * @return void
     */
    public function setRecorder(Recorder $recorder)
    {
        $this->recorder = $recorder;
    }

    /**
     * @return
     */
    public function getRecorder()
    {
        return $this->recorder;
    }
}
