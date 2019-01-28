UPGRADE 3.x
===========

## Deprecated commands that get the cache manager from the container

Any command that extends `Sonata\CacheBundle\Command\BaseCacheCommand` should
provide the `Sonata\Cache\CacheManagerInterface` as a first argument to the
parent class. This should be done automatically in applications that have
autowiring enabled for such commands.
