Schema
======

In the :doc:`../getting-started/index` documentation we already saw how to define a schema for our indexes
and where we have to define them based on our used ``Framework`` or how the create the ``Schema`` instance in
the ``Standalone`` usage.

A Schema is a collection of one or more ``Index`` definitions. An ``Index`` is defined by a name and a list of ``Fields``.
Where every field is defined by a name and a type. All fields types with exception from the ``Identifier``
are possible to be defined as ``filterable``, ``sortable`` and ``multiple``.

Basic Field Types
-----------------

TextField
~~~~~~~~~

The ``Text`` field type is the most important field type. It is used to store PHP ``string`` values.
It is also the only field type that is ``searchable`` via a ``SearchCondition``.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $document = [
        // ...
        "title" => "Title",
        "tags" => ["UI", "UX"],
        "internalNote" => "Some note",
    ];

The following field definitions will show us how we can use ``Text`` fields to index the above fields
via ``sortable``, ``multiple``, ``filterable`` and ``searchable`` flags:

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'title' => new Field\TextField('title', sortable: true),
        'tags' => new Field\TextField('tags', multiple: true, filterable: true),
        'internalNote' => new Field\TextField('internalNote', searchable: false),
    ]);

**Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``filterable``  | ``false``       |
+-----------------+-----------------+
| ``sortable``    | ``false``       |
+-----------------+-----------------+
| ``multiple``    | ``false``       |
+-----------------+-----------------+
| ``searchable``  | ``true``        |
+-----------------+-----------------+

.. note::

    The ``Text`` field type is the only field type which values are ``searchable`` and so the only one kept in mind
    for the ``SearchCondition``. This is because of limitations of different search engines and
    how they are handling different types of data.

IdentifierField
~~~~~~~~~~~~~~~

The ``Identifier`` field type is a special ``Text`` field type. It is used to identify a document in the index.

The document identifier data given requires to be a ``string`` type, beside the ``Text`` type and the other types
it can not be nullable and need always given to the document. It can only be defined once per Index.

The defaults can not be changed and so are same for every index.

Lets have a look at the following example fields of a document:

.. code-block:: php

    <?php

    $document = [
        "id" => "9178e319-326a-447c-801d-93d084de54fc"
        // ...
    ];

The following field definition will show us how to define our ``Identifier`` field:

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'id' => new Field\IdentifierField('id'),
    ]);

**Options:**

Has no configurable options it is always  ``filterable``, but not ``searchable``, ``sortable`` or ``multiple``.

FloatField
~~~~~~~~~~

The ``Float`` field type is used to store numeric values. Unlike the text field type it is
**not** ``searchable``, but the field can be marked as ``filterable`` and ``sortable``.
It is used to store PHP ``float`` values.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $document = [
        // ...
        "rating" => 3.5,
        "points" => [2.5, 5.0],
    ];

The following field definitions will show us how we can use ``Float`` fields to index the above fields
via ``sortable``, ``multiple`` and ``filterable`` flags.

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'rating' => new Field\FloatField('rating', sortable: true, filterable: true),
        'points' => new Field\FloatField('points', multiple: true),
    ]);

**Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``filterable``  | ``false``       |
+-----------------+-----------------+
| ``sortable``    | ``false``       |
+-----------------+-----------------+
| ``multiple``    | ``false``       |
+-----------------+-----------------+

IntegerField
~~~~~~~~~~~~

The ``Integer`` field type is used to store numeric values. Unlike the text field type it is
**not** ``searchable``, but the field can be marked as ``filterable`` and ``sortable``.
It is used to store PHP ``int`` values.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $document = [
        // ...
        "commentCount" => 3,
        "points" => [2, 5],
    ];

The following field definitions will show us how we can use ``Integer`` fields to index the above fields
via ``sortable``, ``multiple`` and ``filterable`` flags.

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'commentCount' => new Field\IntegerField('commentCount', sortable: true, filterable: true),
        'points' => new Field\IntegerField('points', multiple: true),
    ]);

**Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``filterable``  | ``false``       |
+-----------------+-----------------+
| ``sortable``    | ``false``       |
+-----------------+-----------------+
| ``multiple``    | ``false``       |
+-----------------+-----------------+

BooleanField
~~~~~~~~~~~~

The ``Boolean`` field type is used to store flags. Unlike the text field type it is
**not** ``searchable``, but the field can be marked as ``filterable`` and ``sortable``.
It is used to store PHP ``bool`` values.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $document = [
        // ...
        "isSpecial" => true,
        "flags" => [true, false],
    ];

The following field definitions will show us how we can use ``Boolean`` fields to index the above fields
via ``sortable``, ``multiple`` and ``filterable`` flags.

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'isSpecial' => new Field\BooleanField('isSpecial', sortable: true, filterable: true),
        'flags' => new Field\BooleanField('flags', multiple: true),
    ]);

**Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``filterable``  | ``false``       |
+-----------------+-----------------+
| ``sortable``    | ``false``       |
+-----------------+-----------------+
| ``multiple``    | ``false``       |
+-----------------+-----------------+

DateTimeField
~~~~~~~~~~~~~

The ``DateTime`` field type is used to store dates. Unlike the text field type it is
**not** ``searchable``, but the field can be marked as ``filterable`` and ``sortable``.
It uses the PHP ``string`` type and represents the date a date in the ``ISO 8601`` format.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $document = [
        // ...
        "published" => "2004-02-12T15:19:21+00:00",
        "nextDates" => ["2005-02-12T15:19:21+00:00", "2006-02-12T15:19:21+00:00"],
    ];

The following field definitions will show us how we can use ``DateTime`` fields to index the above fields
via ``sortable``, ``multiple`` and ``filterable`` flags.

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'published' => new Field\DateTime('isSpecial', sortable: true, filterable: true),
        'nextDates' => new Field\DateTime('flags', multiple: true),
    ]);

**DateTimeField Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``filterable``  | ``false``       |
+-----------------+-----------------+
| ``sortable``    | ``false``       |
+-----------------+-----------------+
| ``multiple``    | ``false``       |
+-----------------+-----------------+

Complex Field Types
-------------------

ObjectField
~~~~~~~~~~~

The ``Object`` field type is used to index nested objects. Unlike the other field types it is
**not** ``searchable``, ``filterable``, ``sortable`` itself, but can contain fields
which are.

It is represented in PHP as an ``associative array``.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $document = [
        // ...
        "header" => [
            "title" => "Title",
        ],
        "comments" => [
            [
                "text" => "This looks great!",
                "author" => 1,
            ],
            [
                "text" => "What an awesome achievement!",
                "author" => 2,
            ],
        ],
    ];

The following field definitions will show us how we can use ``Object`` fields to index the above fields
via ``multiple`` flags.

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'header' => new Field\ObjectField('header', [
            'title' => new Field\TextField('title'),
        ]),
        'comments' => new Field\ObjectField('comments', [
            'text' => new Field\TextField('text', searchable: false),
            'author' => new Field\IntegerField('author'),
        ], multiple: true),
    ]);

**Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``multiple``    | ``false``       |
+-----------------+-----------------+

TypedField
~~~~~~~~~~

The ``Typed`` field type is a special ``Object`` field type and provides the same functionality.
It is represented in PHP as an ``associative array``. The difference to the ``Object`` field type
is that ``Typed`` can be used to index objects containing different types of fields byed on the
``type`` field.

Lets have a look at the following example fields:

.. code-block:: php

    <?php

    $documentA = [
        // ...
        "header" => [
            "type" => "image",
            "title" => "Title",
            "media" => 1,
        ],
        "blocks" => [
            [
                "type" => "text",
                "title" => "Title",
                "description" => "<p>Description</p>",
                "media" => [3, 4],
            ],
            [
                "type" => "text",
                "title" => "Title 2",
            ],
            [
                "type" => "embed",
                "title" => "Video",
                "media" => "https://www.youtube.com/watch?v=Ix6qBW4a1xg&t=826s",
            ],
            [
                "type" => "text",
                "title" => "Title 4",
                "description" => "<p>Description 4</p>",
                "media" => [3, 4],
            ],
        ],
    ];

    $documentB = [
        // ...
        "header" => [
            "type" => "video",
            "title" => "Title",
            "media" => "https://www.youtube.com/watch?v=Ix6qBW4a1xg&t=826s",
        ],
        "blocks" => [
            [
                "type" => "text",
                "title" => "Title",
                "description" => "<p>Description</p>",
                "media" => [3, 4],
            ],
            [
                "type" => "embed",
                "title" => "Video",
                "media" => "https://www.youtube.com/watch?v=Ix6qBW4a1xg&t=826s",
            ],
        ],
    ];

The following field definitions will show us how we can use ``Typed`` fields to index the above fields
via ``multiple`` and define different types for it.

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'header' => new Field\TypedField('header', 'type', [
            'image' => [
                'title' => new Field\TextField('title'),
                'media' => new Field\IntegerField('media'),
            ],
            'video' => [
                'title' => new Field\TextField('title'),
                'media' => new Field\TextField('media', searchable: false),
            ],
        ]),
        'blocks' => new Field\TypedField('blocks', 'type', [
            'text' => [
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'media' => new Field\IntegerField('media', multiple: true),
            ],
            'embed' => [
                'title' => new Field\TextField('title'),
                'media' => new Field\TextField('media', searchable: false),
            ],
        ], multiple: true),
    ]);

**Options:**

+-----------------+-----------------+
| Name            | Default         |
+=================+=================+
| ``multiple``    | ``false``       |
+-----------------+-----------------+

Create and Drop a Schema
------------------------

After you have defined your ``Schema`` with one or multiple ``Indexes`` you need to create based on your used
integration the ``Indexes`` over the following way:

.. tabs::

    .. group-tab:: Standalone use

        When using the ``Standalone`` version you need to create the ``Indexes``
        in your search engines via the ``Engine`` instance which was created before:

        .. code-block:: php

            <?php

            // create all indexes
            $engine->createSchema();

            // create specific index
            $engine->createIndex('blog');

    .. group-tab:: Laravel

        To create the indexes in Laravel the following artisan command:

        .. code-block:: bash

            # create all indexes
            php artisan cmsig:seal:index-create

            # create specific index
            php artisan cmsig:seal:index-create --index=blog

    .. group-tab:: Symfony

        To create the indexes in Symfony the following console command:

        .. code-block:: bash

            # create all indexes
            bin/console cmsig:seal:index-create

            # create specific index
            bin/console cmsig:seal:index-create --index=blog

    .. group-tab:: Spiral

        To create the indexes in Spiral the following command:

        .. code-block:: bash

            # create all indexes
            php app.php cmsig:seal:index-create

            # create specific index
            php app.php cmsig:seal:index-create --index=blog

    .. group-tab:: Mezzio

        To create the indexes in Mezzio the following command:

        .. code-block:: bash

            # create all indexes
            vendor/bin/laminas cmsig:seal:index-create

            # create specific index
            vendor/bin/laminas cmsig:seal:index-create --index=blog

    .. group-tab:: Yii

        To create the indexes in Yii the following command:

        .. code-block:: bash

            # create all indexes
            ./yii cmsig:seal:index-create

            # create specific index
            ./yii cmsig:seal:index-create --index=blog

To drop a ``Schema`` or an ``Index`` you can use the following:

.. tabs::

    .. group-tab:: Standalone use

        When using the ``Standalone`` version you need to drop the ``Indexes``
        in your search engines via the ``Engine`` instance which was created before:

        .. code-block:: php

            <?php

            // create all indexes
            $engine->dropSchema();

            // create specific index
            $engine->dropIndex('blog');

    .. group-tab:: Laravel

        To drop the indexes in Laravel the following artisan command:

        .. code-block:: bash

            # create all indexes
            php artisan cmsig:seal:index-drop

            # create specific index
            php artisan cmsig:seal:index-drop --index=blog

    .. group-tab:: Symfony

        To drop the indexes in Symfony the following console command:

        .. code-block:: bash

            # create all indexes
            bin/console cmsig:seal:index-drop

            # create specific index
            bin/console cmsig:seal:index-drop --index=blog

    .. group-tab:: Spiral

        To drop the indexes in Spiral the following command:

        .. code-block:: bash

            # create all indexes
            php app.php cmsig:seal:index-drop

            # create specific index
            php app.php cmsig:seal:index-drop --index=blog

    .. group-tab:: Mezzio

        To drop the indexes in Mezzio the following command:

        .. code-block:: bash

            # create all indexes
            vendor/bin/laminas cmsig:seal:index-drop

            # create specific index
            vendor/bin/laminas cmsig:seal:index-drop --index=blog

    .. group-tab:: Yii

        To drop the indexes in Yii the following command:

        .. code-block:: bash

            # create all indexes
            ./yii cmsig:seal:index-drop

            # create specific index
            ./yii cmsig:seal:index-drop --index=blog

----------

Complex Example
---------------

A whole complex example ``Index`` with different types of ``Fields`` for documents like this:

.. code-block:: php

    <?php

    $documentA = [
        'uuid' => '23b30f01-d8fd-4dca-b36a-4710e360a965',
        'title' => 'New Blog',
        'header' => [
            'type' => 'image',
            'media' => 1,
        ],
        'article' => '<article><h2>New Subtitle</h2><p>A html field with some content</p></article>',
        'blocks' => [
            [
                'type' => 'text',
                'title' => 'Title',
                'description' => '<p>Description</p>',
                'media' => [3, 4],
            ],
            [
                'type' => 'text',
                'title' => 'Title 2',
            ],
            [
                'type' => 'embed',
                'title' => 'Video',
                'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
            ],
            [
                'type' => 'text',
                'title' => 'Title 4',
                'description' => '<p>Description 4</p>',
                'media' => [3, 4],
            ],
        ],
        'footer' => [
            'title' => 'New Footer',
        ],
        'created' => '2022-01-24T12:00:00+01:00',
        'commentsCount' => 2,
        'rating' => 3.5,
        'comments' => [
            [
                'email' => 'admin.nonesearchablefield@localhost',
                'text' => 'Awesome blog!',
            ],
            [
                'email' => 'example.nonesearchablefield@localhost',
                'text' => 'Like this blog!',
            ],
        ],
        'tags' => ['Tech', 'UI'],
        'categoryIds' => [1, 2],
    ];

    $documentB = [
        'uuid' => '79848403-c1a1-4420-bcc2-06ed537e0d4d',
        'title' => 'Other Blog',
        'header' => [
            'type' => 'video',
            'media' => 'https://www.youtube.com/watch?v=iYM2zFP3Zn0',
        ],
        'article' => '<article><h2>Other Subtitle</h2><p>A html field with some content</p></article>',
        'footer' => [
            'title' => 'Other Footer',
        ],
        'created' => '2022-12-26T12:00:00+01:00',
        'commentsCount' => 0,
        'rating' => 2.5,
        'comments' => [],
        'tags' => ['UI', 'UX'],
        'categoryIds' => [2, 3],
    ];

Can be saved in an ``Index`` via the following ``Index`` and ``Field`` definitions:

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'uuid' => new Field\IdentifierField('uuid'),
        'title' => new Field\TextField('title'),
        'header' => new Field\TypedField('header', 'type', [
            'image' => [
                'media' => new Field\IntegerField('media'),
            ],
            'video' => [
                'media' => new Field\TextField('media', searchable: false),
            ],
        ]),
        'article' => new Field\TextField('article'),
        'blocks' => new Field\TypedField('blocks', 'type', [
            'text' => [
                'title' => new Field\TextField('title'),
                'description' => new Field\TextField('description'),
                'media' => new Field\IntegerField('media', multiple: true),
            ],
            'embed' => [
                'title' => new Field\TextField('title'),
                'media' => new Field\TextField('media', searchable: false),
            ],
        ], multiple: true),
        'footer' => new Field\ObjectField('footer', [
            'title' => new Field\TextField('title'),
        ]),
        'created' => new Field\DateTimeField('created', filterable: true, sortable: true),
        'commentsCount' => new Field\IntegerField('commentsCount', filterable: true, sortable: true),
        'rating' => new Field\FloatField('rating', filterable: true, sortable: true),
        'comments' => new Field\ObjectField('comments', [
            'email' => new Field\TextField('email', searchable: false),
            'text' => new Field\TextField('text'),
        ], multiple: true),
        'tags' => new Field\TextField('tags', multiple: true, filterable: true),
        'categoryIds' => new Field\IntegerField('categoryIds', multiple: true, filterable: true),
    ]);

Best Practices
--------------

The best practices are to keep your document also when it index complex model as simple as possible.
This means that you concat data from different sources to one field. And create additional fields only
for things which need to be searchable or filterable a special way. A typical search ``Index`` would
look like this:

.. code-block:: php

    <?php

    use CmsIg\Seal\Schema\Field;
    use CmsIg\Seal\Schema\Index;

    $index = new Index('blog', [
        'uuid' => new Field\IdentifierField('uuid'),
        'title' => new Field\TextField('title'),
        'description' => new Field\TextField('description'),
        'url' => new Field\TextField('url'),
        'image' => new Field\IntegerField('image'),
        'content' => new Field\TextField('content', multiple: true),
    ]);

Where the ``content`` field contains all relevant searchable texts. Optionally you maybe have some
category or tags fields which can be filtered on. Too many fields can in different search engines
cost a lot of performance and should only be added when really needed to display or filter on it.
Blocks like in the above complex example can also just concatenated to the ``content`` field which can improve
performance on different engines.

Next Steps
----------

After this deep dive into the field types, we have now covered all the field types that are available
and are able to define complex Indexes via them.

In the next chapter, we will have a look at the :doc:`../indexing/index` before we examine the different conditions of
:doc:`../search-and-filters/index` the abstraction provides.
