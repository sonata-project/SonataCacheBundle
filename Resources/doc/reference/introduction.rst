.. index::
    single: Introduction
    single: Sample
    single: Memcached
    single: MongoDB

Introduction
============

The ``SonataCacheBundle`` provides some facilities to store computed data into a cache backend. Unlike other cache solutions, the ``SonataCacheBundle`` does not use a string as the name for the cache entry.
The name is an array and it is up to the backend solution to compute the best key. On top of that, a cache entry can also have some optional metadata that can be stored along by the cache backend.

So a cache entry is named ``CacheElement`` and has a few fields:
 - ttl: the Time To Live field
 - keys: the information to generate the final key
 - data: the computed data
 - createdAt: the creation date
 - contextualKeys: the array containing some extra metadata


Usage Sample
------------

Let's say you are rendering a blog post that can have many authors and be related to an image. So, you have many information:

- post_id: integer
- author_ids: array
- image_id: integer
- action: view (we are rendering a blog post)

So with the ``SonataCacheBundle``, you will have:

- ``keys=array('post_id' => 1, 'action' => 'view')``: because it is the main information in your example
- ``data=raw html``: the html rendered by the view
- ``contextualKeys: array('post_id' => 1, 'author_ids' => array(1, 2, ...), 'image_id' => 2, 'action' => 'view')``: the contextual keys can contain any information, so we include all information.

Now, let's see how this cache element will be used with 2 backends, `memcached` and `mongodb` (capped collection):

 - The `memcached` adapter will generate a hash from the ``keys`` value, and will not used the ``contextualKeys`` information as there is no way to use those values with memcached.
 - The `mongodb` adapter will store the value as is, mongodb support array! also the ``contextualKeys`` will be stored.

Now, let's try to remove a cache element. This has to be done using the ``flush`` method. The method accepts an array as elements to remove. If you call the function with ``array('post_id' => 1, 'action' => 'view')`` the method will
delete the previous cache entry.

This will work on all adapters as the array is the main key of the ``CacheElement``. Let's see to push this a bit further with the mongodb adapter.

You might want to remove all cache entries when the blog post is saved or when the related image is updated. This actually can be done quite easily just call ``flush``:

.. code-block:: php

    <?php

    // this will flush all entries in the mongodb collection matching this criteria
    $adapter->flush(array('post_id' => 1));

    // this will flush all entries included the post cache as the image_id is part of the contextualKeys element
    $adapter->flush(array('image_id' => 1));

As you can see, the `memcached` driver is quite limited as you can retrieve an element but you cannot do much with invalidation.

