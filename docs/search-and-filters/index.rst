Search & Filter Conditions
==========================

In the :doc:`../getting-started/index` documentation we already saw how we can use the ``SearchBuilder`` to
search for different documents in our indexes.

Beside search functionality the abstraction provides also different kind of filter conditions to build
also complex overview pages for e-commerce or other kind of applications.

The following shows the basic usage as already shown in the "Getting Started" documentation. Under the
``$this->engine`` variable we assume that you have already injected your created ``Engine`` instance.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(/* ... */)
        ->getResult();

    foreach ($result as $document) {
        // do something with the document
    }

    $total = $result->total();

.. note::

    It is also possible to change the index after creating the searchbuilder via ``$searchBuilder->index('other')``.

Conditions
----------

SearchCondition
~~~~~~~~~~~~~~~

The ``SearchCondition`` is the most basic condition and can be used to search for a specific:

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\SearchCondition('Search Term'))
        ->getResult();

The condition does only search on fields which are marked as ``searchable`` in the index configuration.

EqualCondition
~~~~~~~~~~~~~~

The ``EqualCondition`` is used to filter the result by a specific field value matching a given value.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\EqualCondition('tags', 'UI'))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration, it can be also
used on fields which are not marked as ``multiple``.

NotEqualCondition
~~~~~~~~~~~~~~~~~

The ``NotEqualCondition`` is used to filter the result by a specific field value not matching a given value.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\NotEqualCondition('tags', 'UI'))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration, it can be also
used on fields which are not marked as ``multiple``.

IdentifierCondition
~~~~~~~~~~~~~~~~~~~

The ``IdentifierCondition`` is a special kind of ``EqualCondition`` on the identifier field,
if you want to load a document by its identifier this condition is faster in most search engines
then using a ``EqualCondition``.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\IdentifierCondition('23b30f01-d8fd-4dca-b36a-4710e360a965'))
        ->getResult();

GreaterThanCondition
~~~~~~~~~~~~~~~~~~~~

The ``GreaterThanCondition`` is used to filter the result by a specific field value be greater than (``>``)
the given value.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\GreaterThanCondition('rating', 2.5))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration.

GreaterThanEqualCondition
~~~~~~~~~~~~~~~~~~~~~~~~~

The ``GreaterThanEqualCondition`` is used to filter the result by a specific field value be greater than equal (``>=``)
the given value.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\GreaterThanEqualCondition('rating', 2.5))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration.

LessThanCondition
~~~~~~~~~~~~~~~~~

The ``LessThanCondition`` is used to filter the result by a specific field value be less than equal (``<``)
the given value.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\LessThanCondition('rating', 2.5))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration.

LessThanEqualCondition
~~~~~~~~~~~~~~~~~~~~~~

The ``LessThanEqualCondition`` is used to filter the result by a specific field value be less than equal (``<=``)
the given value.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\LessThanEqualCondition('rating', 2.5))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration.

GeoDistanceCondition
~~~~~~~~~~~~~~~~~~~~

The ``GeoDistanceCondition`` is used to filter results within a radius by specifying a latitude, longitude and distance in meters.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('restaurants')
        ->addFilter(new Condition\GeoDistanceCondition('location', 45.472735, 9.184019, 2000))
        ->getResult();

The field is required to be marked as ``filterable`` in the index configuration.

GeoBoundingBoxCondition
~~~~~~~~~~~~~~~~~~~~~~~

The ``GeoBoundingBoxCondition`` is used to filter results within a bounding box by specifying a min latitude, min longitude, max latitude and max longitude.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('restaurants')
        ->addFilter(new Condition\GeoBoundingBoxCondition('location', 45.494181, 9.214024, 45.449484, 9.179175))
        ->getResult();


The field is required to be marked as ``filterable`` in the index configuration.

.. note::

    The ``GeoBoundingBoxCondition`` is currently not supported by ``Redisearch`` adapter.
    See `this Github Issue <https://github.com/RediSearch/RediSearch/issues/680>`__ for more information.

OrCondition
~~~~~~~~~~~

The ``OrCondition`` is used to filter by two or more conditions where at least one condition needs to match.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\OrCondition(
            new Condition\GreaterThanCondition('rating', 2.5),
            new Condition\EqualCondition('isSpecial', true),
        ))
        ->getResult();

The fields are required to be marked as ``filterable`` in the index configuration.

AndCondition
~~~~~~~~~~~~

The ``AndCondition`` is used to combine two or more conditions where all conditions need to match.
By default, all conditions are connected with ``AND``, so it only makes sense to use an ``AndCondition``
in combination with ``OrCondition`` filters.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\AndCondition(
            new Condition\EqualCondition('tags', 'Tech'),
            new Condition\OrCondition(
                new Condition\EqualCondition('tags', 'UX'),
                new Condition\EqualCondition('isSpecial', true),
            ),
        ))
        ->getResult();

The fields are required to be marked as ``filterable`` in the index configuration.


.. note::

    If the ``Algolia`` Adapter is used not all kind of combination with ``OrCondition`` are possible.
    See `this Github Issue <https://github.com/algolia/algoliasearch-client-php/issues/385>`__ for more information.

Filter on Objects and Typed Fields
----------------------------------

To filter on ``Objects`` and ``Typed`` fields you need to use the ``.`` symbol
as a separator between the object and the field.

For example for a document like this where the rating value is filterable:

.. code-block:: php

    <?php

    $document = [
        'rating' => [
            'value' => '1.5'
        ],
    ];

Need to be queried this way `<object>.<field>`:

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\LessThanEqualCondition('rating.value', 2.5))
        ->getResult();

To filter on ``Typed`` objects also the `.` symbol is used but the type name need to be included as well.

For example for a document like this where header media is filterable:

.. code-block:: php

    <?php

    $document = [
        'header' => [
            'type' => 'image',
            'media' => 1
        ],
    ];

Need to be queried this way `<object>.<type>.<field>`:

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(new Condition\EqualCondition('header.image.media', 21))
        ->getResult();

Also nested objects and types can be queried the same way.

--------------

Pagination
----------

Beside the searches and filters you can also limit the result by a given ``limit`` and/or ``offset``.

.. code-block:: php

    <?php

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(/* ... */)
        ->limit(10)
        ->offset(20)
        ->getResult();

With the ``limit`` and ``offset`` also a basic pagination can be created this way:

.. code-block:: php

    <?php

    $page = 1; // get from query parameter
    $pageSize = 10;

    $result = $this->engine->createSearchBuilder('blog')
        ->addFilter(/* ... */)
        ->limit($pageSize)
        ->offset(($page - 1) * $pageSize)
        ->getResult();

    $total = $result->total();
    $maxPage = ceil($total / $pageSize) ?: 1;

    foreach ($result as $document) {
        // do something with the document
    }

--------------

Sorting
-------

The abstraction can also be used to create complex overview pages where you not only can search or filter
your results but also ``sort`` them by a given field.

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addSortBy('rating', 'desc')
        ->getResult();

.. code-block:: php

    <?php

    use CmsIg\Seal\Search\Condition;

    $result = $this->engine->createSearchBuilder('blog')
        ->addSortBy('rating', 'asc')
        ->getResult();

The field is required to be marked as ``sortable`` in the index configuration.

--------------

Summary
-------

After reading this documentation you should have a basic understanding how to use the abstraction
to manage Indexes, add and remove Documents and how to search and filter the results. You should
now be ready to start using the abstraction for your different kind of needs.

Missing something? Let us know by creating an issue
on our `Github Repository <https://github.com/schranz-search/schranz-search>`_.
