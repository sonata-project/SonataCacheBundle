<?php

/*
 * This file is part of the Sonata package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\DependencyInjection\Configuration;

use Sonata\CacheBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * Tests the Configuration class.
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Asserts APC has default timeout values.
     */
    public function testApcDefaultTimeout()
    {
        $expected = array(
            'RCV' => array(),
            'SND' => array(),
        );

        $configs = array(array(
            'caches' => array(
                'apc' => array(
                    'token'  => '',
                    'prefix' => '',
                ),
            ),
        ));

        $config = $this->process($configs);

        $this->assertArrayHasKey('timeout', $config['caches']['apc']);
        $this->assertEquals($expected, $config['caches']['apc']['timeout']);
    }

    /**
     * Asserts APC timeout has custom values.
     */
    public function testApcCustomTimeout()
    {
        $expected = array(
            'RCV' => array('sec' => 10, 'usec' => 0),
            'SND' => array('sec' => 18, 'usec' => 12),
        );

        $configs = array(array(
            'caches' => array(
                'apc' => array(
                    'token'   => '',
                    'prefix'  => '',
                    'timeout' => $expected,
                ),
            ),
        ));

        $config = $this->process($configs);

        $this->assertArrayHasKey('timeout', $config['caches']['apc']);
        $this->assertEquals($expected, $config['caches']['apc']['timeout']);
    }

    /**
     * Processes an array of configurations and returns a compiled version.
     *
     * @param array $configs An array of raw configurations
     *
     * @return array A normalized array
     */
    protected function process(array $configs)
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
