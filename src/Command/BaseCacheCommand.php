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

namespace Sonata\CacheBundle\Command;

use Sonata\Cache\CacheManagerInterface;
use Symfony\Component\Console\Command\Command;

abstract class BaseCacheCommand extends Command
{
    /**
     * @var CacheManagerInterface
     */
    private $cacheManager;

    public function __construct(
        CacheManagerInterface $cacheManager,
        ?string $name = null
    ) {
        parent::__construct($name);

        $this->cacheManager = $cacheManager;
    }

    public function getManager(): CacheManagerInterface
    {
        return $this->cacheManager;
    }
}
