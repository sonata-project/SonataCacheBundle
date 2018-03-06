<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\Tests\DependencyInjection\Configuration;

use PHPUnit\Framework\TestCase;
use Sonata\CacheBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    public function testApcDefaultTimeout(): void
    {
        $configs = [[
            'caches' => [
                'apc' => [
                    'token' => '',
                    'prefix' => '',
                ],
            ],
        ]];

        $config = $this->process($configs);

        $this->assertArrayHasKey('timeout', $config['caches']['apc']);
        $this->assertSame(
            [
                'RCV' => [],
                'SND' => [],
            ],
            $config['caches']['apc']['timeout']
        );
    }

    public function testApcCustomTimeout(): void
    {
        $expected = [
            'RCV' => ['sec' => 10, 'usec' => 0],
            'SND' => ['sec' => 18, 'usec' => 12],
        ];

        $configs = [[
            'caches' => [
                'apc' => [
                    'token' => '',
                    'prefix' => '',
                    'timeout' => $expected,
                ],
            ],
        ]];

        $config = $this->process($configs);

        $this->assertArrayHasKey('timeout', $config['caches']['apc']);
        $this->assertSame($expected, $config['caches']['apc']['timeout']);
    }

    public function testSymfonyDefaultTimeout(): void
    {
        $configs = [[
            'caches' => [
                'symfony' => [
                    'token' => '',
                    'types' => ['all'],
                ],
            ],
        ]];

        $config = $this->process($configs);

        $this->assertArrayHasKey('timeout', $config['caches']['symfony']);
        $this->assertSame(
            [
                'RCV' => ['sec' => 2, 'usec' => 0],
                'SND' => ['sec' => 2, 'usec' => 0],
            ],
            $config['caches']['symfony']['timeout']
        );
    }

    public function testSymfonyCustomTimeout(): void
    {
        $expected = [
            'RCV' => ['sec' => 10, 'usec' => 0],
            'SND' => ['sec' => 18, 'usec' => 12],
        ];

        $configs = [[
            'caches' => [
                'symfony' => [
                    'token' => '',
                    'types' => ['all'],
                    'timeout' => $expected,
                ],
            ],
        ]];

        $config = $this->process($configs);

        $this->assertArrayHasKey('timeout', $config['caches']['symfony']);
        $this->assertSame($expected, $config['caches']['symfony']['timeout']);
    }

    protected function process(array $configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration(new Configuration(), $configs);
    }
}
