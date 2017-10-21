<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Sonata\CacheBundle\Twig\TwigTemplate;

final class TwigTemplateTest extends TestCase
{
    public function testTemplateClassCanBeInstanciated(): void
    {
        $template = $this->prophesize(TwigTemplate::class);
        $this->assertInstanceOf(TwigTemplate::class, $template->reveal());
    }
}
