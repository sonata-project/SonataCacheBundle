<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Twig;

use Sonata\Cache\Invalidation\Recorder;
use Twig\Template;

abstract class TwigTemplate extends Template
{
    /**
     * @var \Sonata\CacheBundle\Invalidation\Recorder
     */
    protected static $recorder;

    /**
     * @static
     *
     * @param \Sonata\Cache\Invalidation\Recorder $recorder
     */
    public static function attachRecorder(Recorder $recorder)
    {
        self::$recorder = $recorder;
    }

    /**
     * @param mixed  $object
     * @param string $item
     * @param array  $arguments
     * @param string $type
     * @param bool   $isDefinedTest
     * @param bool   $ignoreStrictCheck
     *
     * @return mixed
     */
    protected function getAttribute($object, $item, array $arguments = [], $type = Template::ANY_CALL, $isDefinedTest = false, $ignoreStrictCheck = false)
    {
        $attribute = parent::getAttribute($object, $item, $arguments, $type, $isDefinedTest);

        if (self::$recorder && is_object($object)) {
            self::$recorder->add($object);
        }

        return $attribute;
    }
}
