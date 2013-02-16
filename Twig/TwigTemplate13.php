<?php

/*
 * This file is part of sonata-project.
 *
 * (c) 2010 Thomas Rabaix
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Twig;

use Sonata\CacheBundle\Invalidation\Recorder;

abstract class TwigTemplate13 extends \Twig_Template
{
    /**
     * @var \Sonata\CacheBundle\Cache\Invalidation\Recorder
     */
    protected static $recorder;

    /**
     * @param $object
     * @param $item
     * @param  array  $arguments
     * @param  string $type
     * @param  bool   $isDefinedTest
     * @return mixed
     */
    protected function getAttribute($object, $item, array $arguments = array(), $type = \Twig_TemplateInterface::ANY_CALL, $isDefinedTest = false)
    {
        $attribute = parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest);

        if (self::$recorder && is_object($object)) {
            self::$recorder->add($object);
        }

        return $attribute;
    }

    /**
     * @static
     * @param \Sonata\CacheBundle\Invalidation\Recorder $recorder
     */
    public static function attachRecorder(Recorder $recorder)
    {
        self::$recorder = $recorder;
    }
}
