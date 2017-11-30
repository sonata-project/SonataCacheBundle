UPGRADE 2.x
===========

UPGRADE FROM 2.3 to 2.4
=======================

### Deprecated

| deprecated class | recommended class |
|-------------------------|-----------------------------|
| `Sonata\CacheBundle\Invalidation\SimpleCacheInvalidation` | `Sonata\Cache\Invalidation\SimpleCacheInvalidation` |
| `Sonata\CacheBundle\Invalidation\Recorder` | `Sonata\Cache\Invalidation\Recorder` |
| `Sonata\CacheBundle\Invalidation\ModelCollectionIdentifiers` | `Sonata\Cache\Invalidation\ModelCollectionIdentifiers` |
| `Sonata\CacheBundle\Invalidation\InvalidationInterface` | `Sonata\Cache\Invalidation\InvalidationInterface` |
| `Sonata\CacheBundle\Invalidation\DoctrinePHPCRODMListener` | `Sonata\Cache\Invalidation\DoctrinePHPCRODMListener` |
| `Sonata\CacheBundle\Invalidation\DoctrineORMListener` | `Sonata\Cache\Invalidation\DoctrineORMListener` |
| `Sonata\CacheBundle\Cache\CacheManager` | `Sonata\Cache\CacheManager` |
| `Sonata\CacheBundle\Cache\CacheManagerInterface` | `Sonata\Cache\CacheManagerInterface` |
| `Sonata\CacheBundle\Cache\CacheInterface` | `Sonata\Cache\CacheInterface` |
| `Sonata\CacheBundle\Adapter\MemcachedCache` | `Sonata\Cache\Adapter\Cache\MemcachedCache` |
| `Sonata\CacheBundle\Adapter\MongoCache` | `Sonata\Cache\Adapter\Cache\MongoCache` |
| `Sonata\CacheBundle\Adapter\NoopCache` | `Sonata\Cache\Adapter\Cache\NoopCache` |
| `Sonata\CacheBundle\Adapter\PRedisCache` | `Sonata\Cache\Adapter\Cache\PRedisCache` |
| `Sonata\CacheBundle\Twig\TwigTemplate13` | none |
| `Sonata\CacheBundle\Twig\TwigTemplate14` | none |

Both `Sonata\CacheBundle\Adapter\SsiCache` and
`Sonata\CacheBundle\Adapter\VarnishCache` now require you provide their
constructor with an `ArgumentResolverInterface` instance.

### Tests

All files under the ``Tests`` directory are now correctly handled as internal test classes. 
You can't extend them anymore, because they are only loaded when running internal tests. 
More information can be found in the [composer docs](https://getcomposer.org/doc/04-schema.md#autoload-dev).
