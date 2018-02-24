# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

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
