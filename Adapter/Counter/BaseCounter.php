<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Adapter\Counter;

use Sonata\CacheBundle\Counter\Counter;
use Sonata\CacheBundle\Counter\CounterAdapterInterface;

abstract class BaseCounter implements CounterAdapterInterface
{

    /**
     * @param $counter
     *
     * @return Counter
     */
    protected function transform($counter)
    {
        if ($counter instanceof Counter) {
            return $counter;
        }

        return Counter::create($counter);
    }

    /**
     * @param mixed   $value
     * @param Counter $counter
     * @param integer $number
     *
     * @return Counter
     */
    protected function handleIncrement($value, Counter $counter, $number)
    {
        if ($value === false) {
            $counter = $this->set(Counter::create($counter->getName(), $counter->getValue() + $number));
        } else {
            $counter = Counter::create($counter->getName(), $value);
        }

        return $counter;
    }

    /**
     * @param mixed   $value
     * @param Counter $counter
     * @param integer $number
     *
     * @return Counter
     */
    protected function handleDecrement($value, Counter $counter, $number)
    {
        if ($value === false) {
            $counter = $this->set(Counter::create($counter->getName(), $counter->getValue() + (-1 * $number)));
        } else {
            $counter = Counter::create($counter->getName(), $value);
        }

        return $counter;
    }
}
