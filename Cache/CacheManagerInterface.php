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

interface CacheManagerInterface
{
    /**
     * @param $name
     * @param \Sonata\CacheBundle\Cache\CacheInterface $cacheManager
     * @return void
     */
    function addCacheService($name, CacheInterface $cacheManager);

    /**
     * @param $name
     * @return \Sonata\CacheBundle\Cache\CacheInterface
     */
    function getCacheService($name);

    /**
     * Returns related cache services
     *
     * @return array
     */
    function getCacheServices();

    /**
     *
     * @param sring $id
     * @return boolean
     */
    function hasCacheService($id);

    /**
     * @param array $keys
     * @return void
     */
    function invalidate(array $keys);

    /**
     * @param \Sonata\CacheBundle\Invalidation\Recorder $recorder
     * @return void
     */
    function setRecorder(Recorder $recorder);

    /**
     * @return \Sonata\CacheBundle\Invalidation\Recorder
     */
    function getRecorder();
}
