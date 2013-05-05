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
use Sonata\CacheBundle\Invalidation\Recorder;

interface CacheManagerInterface
{
    /**
     * Adds a cache service
     *
     * @param string         $name         A cache name
     * @param CacheInterface $cacheManager A cache service
     */
    function addCacheService($name, CacheInterface $cacheManager);

    /**
     * Gets a cache service by a given name
     *
     * @param string $name A cache name
     *
     * @return CacheInterface
     */
    function getCacheService($name);

    /**
     * Returns related cache services
     *
     * @return array
     */
    function getCacheServices();

    /**
     * Returns TRUE whether a cache service identified by id exists
     *
     * @param string $id
     *
     * @return boolean
     */
    function hasCacheService($id);

    /**
     * Invalidates the cache by the given keys
     *
     * @param array $keys
     */
    function invalidate(array $keys);

    /**
     * Sets the recorder
     *
     * @param Recorder $recorder
     */
    function setRecorder(Recorder $recorder);

    /**
     * Gets the recorder
     *
     * @return Recorder
     */
    function getRecorder();
}