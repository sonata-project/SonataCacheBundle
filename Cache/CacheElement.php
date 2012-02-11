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

final class CacheElement
{
    protected $ttl;

    protected $keys = array();

    protected $data;

    protected $createdAt;

    protected $contextualKeys = array();

    /**
     * @param array $keys
     * @param $data
     * @param int $ttl
     * @param array $contextualKeys
     */
    public function __construct(array $keys, $data, $ttl = 84600, array $contextualKeys = array())
    {
        $this->createdAt = new \DateTime;
        $this->keys      = $keys;
        $this->ttl       = $ttl;
        $this->data      = $data;
        $this->contextualKeys = $contextualKeys;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @return int
     */
    public function getTtl()
    {
        return $this->ttl;
    }

    /**
     * @return
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isExpired()
    {
        return strtotime('now') > ($this->createdAt->format('U') + $this->ttl);
    }

    /**
     * @return array
     */
    public function getContextualKeys()
    {
        return $this->contextualKeys;
    }
}