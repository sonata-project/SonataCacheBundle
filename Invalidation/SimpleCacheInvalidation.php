<?php
/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Invalidation;

use Sonata\CacheBundle\Cache\CacheInterface;
use Symfony\Component\HttpKernel\Log\LoggerInterface;

class SimpleCacheInvalidation implements InvalidationInterface
{
    protected $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function invalidate(array $caches, array $keys)
    {
        foreach ($caches as $cache) {

            if (!$cache instanceof CacheInterface) {
                throw new \RuntimeException('The object must implements the CacheInterface interface');
            }

            try {
                if ($this->logger) {
                    $this->logger->info(sprintf('[%s] flushing cache keys : %s', __CLASS__, json_encode($keys)));
                }

                $cache->flush($keys);

            } catch (\Exception $e) {

                if ($this->logger) {
                    $this->logger->alert(sprintf('[%s] %s', __CLASS__, $e->getMessage()));
                } else {
                    throw new \RunTimeException(null, null, $e);
                }
            }
        }

        return true;
    }
}
