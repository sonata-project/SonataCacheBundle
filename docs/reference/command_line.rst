.. index::
    single: Command Lines

Command Line Tools
==================

- Flush cache elements matching the key ``block_id = 5``:

.. code-block:: bash

    bin/console sonata:cache:flush --keys='{"block_id":5}'

- Flush cache elements from Varnish and Memcached matching the key ``block_id = 5``:

.. code-block:: bash

    bin/console sonata:cache:flush --keys='{"block_id":5}' --cache=sonata.page.cache.esi --cache=sonata.cache.memcached

- Flush all cache elements:

.. code-block:: bash

    bin/console sonata:cache:flush-all

- Flush all cache elements from Varnish:

.. code-block:: bash

    bin/console sonata:cache:flush-all --cache=sonata.page.cache.esi

