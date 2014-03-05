.. index::
    single: Counter
    single: Adapters
    single: APC
    single: Routing
    single: MongoDB
    single: MemcacheD
    single: PRedis

Counter
=======

The ``SonataCacheBundle`` comes with counter adapters to store value:

- A counter is a simple object with a name and a value
- An adapter contains methods to increment or decrement a counter


Adapters
--------

- ``sonata.cache.counter.mongo``: use `MongoDB` to store counters in a dedicated collection
- ``sonata.cache.counter.predis``: use `PRedis` to store counters in a Redis database
- ``sonata.cache.counter.memcached``: use `MemcacheD` to store counters in a shared and volatile storage
- ``sonata.cache.counter.apc``: use `APC` to store counters in the current PHP process shared memory

`MongoDB` or `PRedis` must be used in production, `memcached` and `apc` might be used to solve specifics use cases.

Usage
-----

.. code-block:: php

    <?php

    use Sonata\CacheBundle\Adapter\Counter\PRedisCounter;
    use Sonata\CacheBundle\Counter\Counter;

    $adapter = PRedisCounter(array(
        'host'     => '127.0.0.1',
        'port'     => 6379,
        'database' => 42
    ));


    $counter = $adapter->increment("mystats");

    // $counter is a Counter object
    $counter->getValue(); // will return 1 if the counter is new

    $counter = $adapter->increment($counter, 10);

    $counter->getValue(); // will return 11

Configuration
-------------

To use the counter feature, add the following lines to your application configuration file:

.. code-block:: yaml

    # app/config/config.yml
    sonata_cache:
        default_counter: sonata.cache.counter.mongo

        counters:
            mongo:                   # sonata.cache.counter.mongo
                database:   cache
                collection: cache
                servers:
                    - {host: 127.0.0.1, port: 27017, user: username, password: pASS'}
                    - {host: 127.0.0.2}

            memcached:                # sonata.cache.counter.memcached
                prefix: test          # prefix to ensure there is no clash between instances
                servers:
                    - {host: 127.0.0.1, port: 11211, weight: 0}

            predis:                  # sonata.cache.counter.predis
                servers:
                    - {host: 127.0.0.1, port: 11211, database: 6379}

            apc:                     # sonata.cache.counter.apc
                prefix: test         # prefix to ensure there is no clash between instances


The ``default_counter`` defines a global counter service name ``sonata.cache.counter``.