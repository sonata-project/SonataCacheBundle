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

use Sonata\Cache\Invalidation\Recorder;
use Twig_Environment;
use Twig_Extension;

/**
 * @internal
 *
 * Ugly hack to run this code on twig initialization
 *
 * NEXT_MAJOR: remove
 */
final class TwigTemplateRecorderInjector extends Twig_Extension
{
    public function __construct(Twig_Environment $twig, Recorder $recorder)
    {
        $baseTemplateClass = $twig->getBaseTemplateClass();

        if (empty($baseTemplateClass)) {
            return;
        }

        if (method_exists($baseTemplateClass, 'attachRecorder')) {
            call_user_func([$baseTemplateClass, 'attachRecorder'], $recorder);
        }
    }
}
