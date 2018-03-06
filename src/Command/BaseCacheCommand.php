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

abstract class BaseCacheCommand extends ContainerAwareCommand
{
    public function getManager(): CacheManagerInterface
    {
        return $this->getContainer()->get('sonata.cache.manager');
    }
}
