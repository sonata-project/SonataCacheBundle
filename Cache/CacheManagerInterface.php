<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Cache;

@trigger_error(
    'The '.__NAMESPACE__.'\CacheManagerInterface class is deprecated since version 2.4 and will be removed in 3.0.'
    .' Use Sonata\Cache\CacheManagerInterface instead.',
    E_USER_DEPRECATED
);

/**
 * NEXT_MAJOR: remove this class.
 *
 * @deprecated since version 2.4, to be removed in 3.0. Use Sonata\Cache\CacheManagerInterface instead.
 */
interface CacheManagerInterface extends \Sonata\Cache\CacheManagerInterface
{
}
