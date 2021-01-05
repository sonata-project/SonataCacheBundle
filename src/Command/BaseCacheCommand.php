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
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * NEXT_MAJOR: stop extending ContainerAwareCommand.
 */
abstract class BaseCacheCommand extends ContainerAwareCommand
{
    /**
     * @var ?CacheManagerInterface
     */
    private $cacheManager;

    public function __construct(
        ?string $name = null,
        ?CacheManagerInterface $cacheManager = null
    ) {
        parent::__construct($name);
        if (null === $cacheManager) {
            @trigger_error(sprintf(
                'Not providing a cache manager to "%s" is deprecated since 3.x and will no longer be possible in 4.0',
                static::class
            ), E_USER_DEPRECATED);
        }
        $this->cacheManager = $cacheManager;
    }

    public function getManager(): CacheManagerInterface
    {
        if (null === $this->cacheManager) {
            return $this->getContainer()->get(CacheManagerInterface::class);
        }

        return $this->cacheManager;
    }
}
