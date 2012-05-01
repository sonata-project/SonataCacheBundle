Installation
============

To begin, add the dependent bundles to the vendor/bundles directory. Add the following lines to the file deps::

    [SonataCacheBundle]
        git=http://github.com/sonata-project/SonataCacheBundle.git
        target=/bundles/Sonata/CacheBundle
        version=origin/2.0


Now, add the new Bundle to the kernel

.. code-block:: php

    <?php
    public function registerbundles()
    {
        return array(
            // Vendor specifics bundles
            new Sonata\CacheBundle\SonataCacheBundle(),
        );
    }

Update the ``autoload.php`` to add new namespaces:

.. code-block:: php

    <?php
    $loader->registerNamespaces(array(
        'Sonata'                             => __DIR__,

        // ... other declarations
    ));


Configuration
-------------

To use the ``CacheBundle``, add the following lines to your application configuration
file.

.. code-block:: yaml

    # app/config/config.yml
    sonata_cache:
        caches:
            esi:
                servers:
                    - varnishadm -T 127.0.0.1:2000 {{ COMMAND }} "{{ EXPRESSION }}"

            mongo:
                database:   cache
                collection: cache
                servers:
                    - {host: 127.0.0.1, port: 27017, user: username, password: pASS'}
                    - {host: 127.0.0.2}

            memcached:
                prefix: test     # prefix to ensure there is no clash between instances
                servers:
                    - {host: 127.0.0.1, port: 11211, weight: 0}

            memcache:
                prefix: test     # prefix to ensure there is no clash between instances
                servers:
                    - {host: 127.0.0.1, port: 11211, weight: 1}

            apc:
                token:  s3cur3   # token used to clear the related cache
                prefix: test     # prefix to ensure there is no clash between instances
                servers:
                    - { domain: kooqit.local, ip: 127.0.0.1, port: 80}


At the end of your routing file, add the following lines

.. code-block:: yaml

    # app/config/routing.yml
    sonata_page_cache:
        resource: '@SonataCacheBundle/Resources/config/routing/cache.xml'
        prefix: /
