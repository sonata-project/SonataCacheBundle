# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## [3.3.1](https://github.com/sonata-project/SonataCacheBundle/compare/3.3.0...3.3.1) - 2021-12-04
### Fixed
- [[#384](https://github.com/sonata-project/SonataCacheBundle/pull/384)] Fixed command registration ([@jordisala1991](https://github.com/jordisala1991))
- [[#384](https://github.com/sonata-project/SonataCacheBundle/pull/384)] Fixed inconsistent return types ([@jordisala1991](https://github.com/jordisala1991))

## [3.3.0](https://github.com/sonata-project/SonataCacheBundle/compare/3.2.1...3.3.0) - 2021-03-28
### Added
- [[#321](https://github.com/sonata-project/SonataCacheBundle/pull/321)] Add Symfony 5 support ([@VincentLanglet](https://github.com/VincentLanglet))
- [[#309](https://github.com/sonata-project/SonataCacheBundle/pull/309)] Add support for PHP 8.x ([@Yozhef](https://github.com/Yozhef))

## [3.2.1](https://github.com/sonata-project/SonataCacheBundle/compare/3.2.0...3.2.1) - 2020-01-28
### Fixed
- Changed return type declarations from `void` to `int` on the commands
  `execute()` methods

## [3.2.0](https://github.com/sonata-project/SonataCacheBundle/compare/3.1.0...3.2.0) - 2020-01-28
### Added
- Added symfony 5 components in composer.json

## Removed
- Removed support for symfony 3

## [3.1.0](https://github.com/sonata-project/SonataCacheBundle/compare/3.0.1...3.1.0) - 2019-06-03

### Fixed
- missing argument for SSI and ESI cache
- Fix return code of flush command for symfony cache
- crash when running `sonata:cache:flush-all` or `sonata:cache:flush`
- Fix deprecation for symfony/config 4.2+

### Deprecated
- not providing a cache manager to commands extending
  `Sonata\CacheBundle\Command\BaseCacheCommand`

## [3.0.1](https://github.com/sonata-project/SonataCacheBundle/compare/3.0.0...3.0.1) - 2018-12-15

### Fixed
- Wrong namespace for `sonata.cache.phpcr_odm.event_subscriber.default` config
- `sonata.cache.phpcr_odm.event_subscriber.default`is now `public`
- definition pointing to a non existent class

## [3.0.0](https://github.com/sonata-project/SonataCacheBundle/compare/2.4.2...3.0.0) - 2018-06-27

### Added

- support for sonata/cache 2

### Removed

- support for sonata/cache 1
- support for Twig 1
- PHP 5.3 through 5.5 support was removed
- Symfony 2.2 through 2.7 support was removed
- removed deprecated twig template classes
- removed calling deprecated twig classes in bundle boot

### Fixed

- Fixed sonata.cache.invalidation.simple service definition typo

### Changed

- `Sonata\CacheBundle\Twig\TwigTemplate14` was renamed to `Sonata\CacheBundle\Twig\TwigTemplate`

## [2.4.2](https://github.com/sonata-project/SonataCacheBundle/compare/2.4.1...2.4.2) - 2018-03-12
### Fixed
- Usage of twig service in SonataCacheBundle is optional now

## [2.4.1](https://github.com/sonata-project/SonataCacheBundle/compare/2.4.0...2.4.1) - 2018-02-23
### Changed
- made service public

### Fixed
- Symfony 4.0 for `symfony/config`
- Commands not working on symfony4

## [2.4.0](https://github.com/sonata-project/SonataCacheBundle/compare/2.3.1...2.4.0) - 2017-11-30
### Added
- Added password configuration option under Predis cache configuration.

### Changed
- Rollback to PHP 5.6 as minimum support.
- Changed internal folder structure to `src`, `tests` and `docs`

### Deprecated
- `Sonata\CacheBundle\Invalidation\SimpleCacheInvalidation`, use `Sonata\Cache\Invalidation\SimpleCacheInvalidation` instead
- `Sonata\CacheBundle\Invalidation\Recorder`, use `Sonata\Cache\Invalidation\Recorder` instead.
- `Sonata\CacheBundle\Invalidation\ModelCollectionIdentifiers`, use `Sonata\Cache\Invalidation\ModelCollectionIdentifiers` instead
- `Sonata\CacheBundle\Invalidation\InvalidationInterface`, use `Sonata\Cache\Invalidation\InvalidationInterface` instead
- `Sonata\CacheBundle\Invalidation\DoctrinePHPCRODMListener`, use `Sonata\Cache\Invalidation\DoctrinePHPCRODMListener` instead
- `Sonata\CacheBundle\Invalidation\DoctrineORMListener`, use `Sonata\Cache\Invalidation\DoctrineORMListener` instead
- `Sonata\CacheBundle\Cache\CacheManager`, use `Sonata\Cache\CacheManager` instead.
- `Sonata\CacheBundle\Cache\CacheManagerInterface`, use `Sonata\Cache\CacheManagerInterface` instead
- `Sonata\CacheBundle\Cache\CacheInterface`, use `Sonata\Cache\CacheInterface` instead.
- `Sonata\CacheBundle\Adapter\MemcachedCache`, use `Sonata\Cache\Adapter\Cache\MemcachedCache` instead
- `Sonata\CacheBundle\Adapter\MongoCache`, use `Sonata\Cache\Adapter\Cache\MongoCache` instead
- `Sonata\CacheBundle\Adapter\NoopCache`, use `Sonata\Cache\Adapter\Cache\NoopCache` instead
- `Sonata\CacheBundle\Adapter\PRedisCache`, use `Sonata\Cache\Adapter\Cache\PRedisCache` instead
- Deprecation warning when using `\Twig_Template::getAttribute` function.
- omitting the `$argumentsResolver` argument when instanciating `SsiCache` or `VarnishCache`

### Fixed
- DoctrineBundle and DoctrinePHPCRBundle can be loaded before or after SonataCacheBundle.
- deprecation about using a `ControllerResolverInterface` to resolve arguments
- It is now allowed to install Symfony 4

### Removed
- Support for PHP <7.1
- Support for old versions of PHP and Symfony.

## [2.3.1](https://github.com/sonata-project/SonataCacheBundle/compare/2.3.0...2.3.1) - 2016-09-06
### Fixed
- Fixed variable conflict when multiple Varnish servers are configured

## [2.3.0](https://github.com/sonata-project/SonataCacheBundle/compare/2.2.5...2.3.0) - 2016-09-02
### Added
- Possibility to configure the timeout to clear the Symfony cache

### Fixed
- IP detection in SymfonyCache
