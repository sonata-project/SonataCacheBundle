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
use Twig_Extension_InitRuntimeInterface;

/**
 * @internal
 *
 * NEXT_MAJOR: remove
 */
final class TwigTemplateRecorderInjector extends Twig_Extension implements Twig_Extension_InitRuntimeInterface
{
    /**
     * @var Recorder
     */
    private $recorder;

    public function __construct(Recorder $recorder)
    {
        $this->recorder = $recorder;
    }

    /**
     * Initializes the runtime environment.
     *
     * This is where you can load some file that contains filter functions for instance.
     *
     * @param Twig_Environment $environment The current Twig_Environment instance
     */
    public function initRuntime(Twig_Environment $environment)
    {
        $baseTemplateClass = $environment->getBaseTemplateClass();

        if (empty($baseTemplateClass)) {
            return;
        }

        if (method_exists($baseTemplateClass, 'attachRecorder')) {
            call_user_func([$baseTemplateClass, 'attachRecorder'], $this->recorder);
        }
    }
}
