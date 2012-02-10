Command Line Tools
==================

- Flush cache elements matching the key ``block_id = 5``::

    php app/console sonata:cache:flush --keys='{"block_id":5}'

- Flush all cache elements::

    php app/console sonata:cache:flush-all

