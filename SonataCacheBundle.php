<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle;

use Sonata\CacheBundle\DependencyInjection\Compiler\CacheCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SonataCacheBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new CacheCompilerPass());
    }

    public function boot()
    {
        $baseTemplateClass = $this->container->get('twig')->getBaseTemplateClass();

        if (empty($baseTemplateClass)) {
            return;
        }

        if (method_exists($baseTemplateClass, 'attachRecorder')) {
            call_user_func(array($baseTemplateClass, 'attachRecorder'), $this->container->get('sonata.cache.recorder'));
        }
    }
}
