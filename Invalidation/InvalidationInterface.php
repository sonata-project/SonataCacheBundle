<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Invalidation;

@trigger_error(
    'The '.__NAMESPACE__.'\InvalidationInterface class is deprecated since version 2.4 and will be removed in 3.0.'
    .' Use Sonata\Cache\Invalidation\InvalidationInterface instead.',
    E_USER_DEPRECATED
);

/**
 * NEXT_MAJOR: remove this class.
 *
 * @deprecated since version 2.4, to be removed in 3.0. Use Sonata\Cache\Invalidation\InvalidationInterface instead.
 */
interface InvalidationInterface extends \Sonata\Cache\Invalidation\InvalidationInterface
{
}
