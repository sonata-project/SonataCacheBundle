<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Cache;

interface CacheInterface
{
    /**
     * @param array $keys
     *
     * @return \Sonata\CacheBundle\Cache\CacheElement
     */
    function get(array $keys);

    /**
     * @param array $keys
     *
     * @return boolean
     */
    function has(array $keys);

    /**
     * @param array $keys
     * @param $value
     * @param int $ttl
     * @param array $contextualKeys
     *
     * @return void
     */
    function set(array $keys, $value, $ttl = 84600, array $contextualKeys = array());

    /**
     * @param array $keys
     *
     * @return boolean
     */
    function flush(array $keys = array());

    /**
     * @return boolean
     */
    function flushAll();

    /**
     * @return boolean
     */
    function isContextual();
}