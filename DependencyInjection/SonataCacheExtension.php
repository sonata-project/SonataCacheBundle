<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\CacheBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Definition\Processor;

/**
 * PageExtension
 *
 *
 * @author     Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class SonataCacheExtension extends Extension
{
    /**
     * Loads the url shortener configuration.
     *
     * @param array            $configs   An array of configuration settings
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('cache.xml');
        $loader->load('counter.xml');

        $useOrm = 'auto' == $config['cache_invalidation']['orm_listener'] ?
            class_exists('Doctrine\\ORM\\Version') :
            $config['cache_invalidation']['orm_listener'];

        if ($useOrm) {
            $loader->load('orm.xml');
        }

        $usePhpcrOdm = 'auto' == $config['cache_invalidation']['phpcr_odm_listener'] ?
            class_exists('Doctrine\\PHPCR\\ODM\\Version') :
            $config['cache_invalidation']['phpcr_odm_listener'];
        if ($usePhpcrOdm) {
            $loader->load('phpcr_odm.xml');
        }

        $this->configureInvalidation($container, $config);
        if ($useOrm) {
            $this->configureORM($container, $config);
        }
        if ($usePhpcrOdm) {
            $this->configurePHPCRODM($container, $config);
        }
        $this->configureCache($container, $config);
        $this->configureCounter($container, $config);
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return void
     */
    public function configureInvalidation(ContainerBuilder $container, $config)
    {
        $cacheManager = $container->getDefinition('sonata.cache.manager');

        $cacheManager->replaceArgument(0, new Reference($config['cache_invalidation']['service']));

        $recorder = $container->getDefinition('sonata.cache.model_identifier');
        foreach ($config['cache_invalidation']['classes'] as $class => $method) {
            $recorder->addMethodCall('addClass', array($class, $method));
        }

        $cacheManager->addMethodCall('setRecorder', array(new Reference($config['cache_invalidation']['recorder'])));
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return void
     */
    public function configureORM(ContainerBuilder $container, $config)
    {
        $cacheManager = $container->getDefinition('sonata.cache.orm.event_subscriber');

        $connections = array_keys($container->getParameter('doctrine.connections'));
        foreach ($connections as $conn) {
            $cacheManager->addTag('doctrine.event_subscriber', array('connection' => $conn));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return void
     */
    public function configurePHPCRODM(ContainerBuilder $container, $config)
    {
        $cacheManager = $container->getDefinition('sonata.cache.phpcr_odm.event_subscriber');

        $sessions = array_keys($container->getParameter('doctrine_phpcr.odm.sessions'));
        foreach ($sessions as $session) {
            $cacheManager->addTag('doctrine_phpcr.event_subscriber', array('session' => $session));
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return void
     *
     * @throws \RuntimeException if the Mongo or Memcached library is not installed
     */
    public function configureCache(ContainerBuilder $container, $config)
    {
        if ($config['default_cache']) {
            $container->setAlias('sonata.cache', $config['default_cache']);
        }

        if (isset($config['caches']['esi'])) {
            $container
                ->getDefinition('sonata.cache.esi')
                ->replaceArgument(0, $config['caches']['esi']['token'])
                ->replaceArgument(1, $config['caches']['esi']['servers'])
                ->replaceArgument(3, 3 === $config['caches']['esi']['version'] ? 'ban' : 'purge');
        } else {
            $container->removeDefinition('sonata.cache.esi');
        }

        if (isset($config['caches']['ssi'])) {
            $container
                ->getDefinition('sonata.cache.ssi')
                ->replaceArgument(0, $config['caches']['ssi']['token'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.ssi');
        }

        if (isset($config['caches']['mongo'])) {
            $this->checkMongo();

            $servers = array();
            foreach ($config['caches']['mongo']['servers'] as $server) {
                if ($server['user']) {
                    $servers[] = sprintf('%s:%s@%s:%s', $server['user'], $server['password'], $server['host'], $server['port']);
                } else {
                    $servers[] = sprintf('%s:%s', $server['host'], $server['port']);
                }
            }

            $container
                ->getDefinition('sonata.cache.mongo')
                ->replaceArgument(0, $servers)
                ->replaceArgument(1, $config['caches']['mongo']['database'])
                ->replaceArgument(2, $config['caches']['mongo']['collection'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.mongo');
        }

        if (isset($config['caches']['memcached'])) {

            $this->checkMemcached();

            $container
                ->getDefinition('sonata.cache.memcached')
                ->replaceArgument(0, $config['caches']['memcached']['prefix'])
                ->replaceArgument(1, $config['caches']['memcached']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.memcached');
        }

        if (isset($config['caches']['predis'])) {

            $this->checkPRedis();

            $container
                ->getDefinition('sonata.cache.predis')
                ->replaceArgument(0, $config['caches']['predis']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.predis');
        }

        if (isset($config['caches']['apc'])) {

            $this->checkApc();

            $container
                ->getDefinition('sonata.cache.apc')
                ->replaceArgument(1, $config['caches']['apc']['token'])
                ->replaceArgument(2, $config['caches']['apc']['prefix'])
                ->replaceArgument(3, $this->configureServers($config['caches']['apc']['servers']))
            ;
        } else {
            $container->removeDefinition('sonata.cache.apc');
        }

        if (isset($config['caches']['symfony'])) {
            $container
                ->getDefinition('sonata.cache.symfony')
                ->replaceArgument(3, $config['caches']['symfony']['token'])
                ->replaceArgument(4, $config['caches']['symfony']['php_cache_enabled'])
                ->replaceArgument(5, $config['caches']['symfony']['types'])
                ->replaceArgument(6, $this->configureServers($config['caches']['symfony']['servers']))
            ;
        } else {
            $container->removeDefinition('sonata.cache.symfony');
        }
    }

    /**
     * @param ContainerBuilder $container
     * @param array            $config
     *
     * @return void
     *
     * @throws \RuntimeException if the Mongo or Memcached library is not installed
     */
    public function configureCounter(ContainerBuilder $container, $config)
    {
        if ($config['default_counter']) {
            $container->setAlias('sonata.counter', $config['default_counter']);
        }

        if (isset($config['counters']['mongo'])) {
            $this->checkMongo();

            $servers = array();
            foreach ($config['counters']['mongo']['servers'] as $server) {
                if ($server['user']) {
                    $servers[] = sprintf('%s:%s@%s:%s', $server['user'], $server['password'], $server['host'], $server['port']);
                } else {
                    $servers[] = sprintf('%s:%s', $server['host'], $server['port']);
                }
            }

            $container
                ->getDefinition('sonata.cache.counter.mongo')
                ->replaceArgument(0, $servers)
                ->replaceArgument(1, $config['counters']['mongo']['database'])
                ->replaceArgument(2, $config['counters']['mongo']['collection'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.counter.mongo');
        }

        if (isset($config['counters']['memcached'])) {

            $this->checkMemcached();

            $container
                ->getDefinition('sonata.cache.counter.memcached')
                ->replaceArgument(0, $config['counters']['memcached']['prefix'])
                ->replaceArgument(1, $config['counters']['memcached']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.counter.memcached');
        }

        if (isset($config['counters']['predis'])) {

            $this->checkPRedis();

            $container
                ->getDefinition('sonata.cache.counter.predis')
                ->replaceArgument(0, $config['counters']['predis']['servers'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.counter.predis');
        }

        if (isset($config['counters']['apc'])) {

            $this->checkApc();

            $container
                ->getDefinition('sonata.cache.counter.apc')
                ->replaceArgument(0, $config['counters']['apc']['prefix'])
            ;
        } else {
            $container->removeDefinition('sonata.cache.counter.apc');
        }
    }

    protected function checkMemcached()
    {
        if (!class_exists('\Memcached', true)) {
            throw new \RuntimeException(<<<HELP
The `sonata.cache.memcached` service is configured, however the Memcached class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.memcached.php
or remove the memcached cache settings from the configuration file.
HELP
            );
        }
    }

    protected function checkApc()
    {
        if (!function_exists('apc_fetch')) {
            throw new \RuntimeException(<<<HELP
The `sonata.cache.apc` service is configured, however the apc_* functions are not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.apc.php
or remove the APC cache settings from the configuration file.
HELP
            );
        }
    }

    protected function checkMongo()
    {
        if (!class_exists('\Mongo', true)) {
            throw new \RuntimeException(<<<HELP
The `sonata.cache.mongo` service is configured, however the Mongo class is not available.

To resolve this issue, please install the related library : http://php.net/manual/en/book.mongo.php
or remove the mongo cache settings from the configuration file.
HELP
            );
        }
    }

    protected function checkPRedis()
    {
        if (!class_exists('\Predis\Client', true)) {
            throw new \RuntimeException(<<<HELP
The `sonata.cache.predis` service is configured, however the Predis\Client class is not available.

Please add the lib in your composer.json file: "predis/predis": "~0.8".
HELP
            );
        }
    }

    /**
     * Returns servers list with hash for basic auth computed if provided
     *
     * @param array $servers
     *
     * @return array
     */
    public function configureServers(array $servers)
    {
        return array_map(
            function($item) {
                if ($item['basic']) {
                    $item['basic'] = base64_encode($item['basic']);
                }

                return $item;
            },
            $servers
        );
    }
}