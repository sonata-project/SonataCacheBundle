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
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Extension\InitRuntimeInterface;

/**
 * @internal
 *
 * NEXT_MAJOR: remove
 */
final class TwigTemplateRecorderInjector extends AbstractExtension implements InitRuntimeInterface
{
    /**
     * @var Recorder
     */
    private $recorder;

    public function __construct(Recorder $recorder)
    {
        $this->recorder = $recorder;
    }

    public function initRuntime(Environment $environment)
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
