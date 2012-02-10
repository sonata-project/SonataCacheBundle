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

use Sonata\BlockBundle\Cache\CacheInterface;
use Sonata\CacheBundle\Cache\CacheElement;

class NoopCache implements CacheInterface
{
    public function flushAll()
    {
        return true;
    }

    public function flush(array $keys = array())
    {
        return true;
    }

    public function has(CacheElement $cacheElement)
    {
        return false;
    }

    public function set(CacheElement $cacheElement)
    {
        return true;
    }

    /**
     * @param array $parameters
     * @return string
     */
    public function get(CacheElement $cacheElement)
    {
        throw new \RunTimeException('The NoopCache::get() cannot called');
    }

    public function isContextual()
    {
        return true;
    }
}