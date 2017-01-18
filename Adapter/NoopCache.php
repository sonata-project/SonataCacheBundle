<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Adapter;

@trigger_error(
    'The '.__NAMESPACE__.'\NoopCache class is deprecated since version 2.4 and will be removed in 3.0.'
    .' Use Sonata\Cache\Adapter\Cache\NoopCache instead.',
    E_USER_DEPRECATED
);

/**
 * NEXT_MAJOR: remove this class.
 *
 * @deprecated since version 2.4, to be removed in 3.0. Use Sonata\Cache\Adapter\Cache\NoopCache instead.
 */
class NoopCache extends \Sonata\Cache\Adapter\Cache\NoopCache
{
}
