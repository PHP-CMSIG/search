Functional Tests
=================

Search engines index documents asynchronously. If a test saves a document and
immediately searches for it, the search engine may not have processed the write
yet and the assertion will fail intermittently. This cookbook shows the pattern
used across this project's own test suites to write reliable functional tests
against a real ``Engine``:

- create the index (together with the rest of your test fixtures, e.g. your
  application's database) once in your test bootstrap,
- use the ``return_slow_promise_result`` option to index documents synchronously,
- clean up the documents you created once the test is done.

Bootstrap the index together with your test database
------------------------------------------------------

Just like your application's database, the search index needs to exist before
your functional tests run. The cleanest place to do this is the same bootstrap
step that already prepares your test database, so both are always in sync.

.. tabs::

    .. group-tab:: Standalone use

        Build the ``Engine`` once for the whole test run and create its schema
        from your PHPUnit bootstrap file, next to whatever prepares your
        application's database. A small static helper, the same way the
        project's own adapter test suites do it (see ``ClientHelper`` in
        `packages/seal-loupe-adapter/tests <https://github.com/php-cmsig/search/blob/0.12/packages/seal-loupe-adapter/tests/ClientHelper.php>`__),
        keeps the ``Engine`` reachable from every test:

        .. code-block:: php

            <?php // tests/EngineHelper.php

            declare(strict_types=1);

            namespace App\Tests;

            use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
            use CmsIg\Seal\Adapter\Loupe\LoupeHelper;
            use CmsIg\Seal\Engine;
            use CmsIg\Seal\Schema\Field;
            use CmsIg\Seal\Schema\Index;
            use CmsIg\Seal\Schema\Schema;
            use Loupe\Loupe\LoupeFactory;

            final class EngineHelper
            {
                private static Engine|null $engine = null;

                public static function get(): Engine
                {
                    if (!self::$engine instanceof Engine) {
                        $schema = new Schema([
                            'blog' => new Index('blog', [
                                'id' => new Field\IdentifierField('id'),
                                'title' => new Field\TextField('title'),
                                'tags' => new Field\TextField('tags', multiple: true, filterable: true),
                            ]),
                        ]);

                        self::$engine = new Engine(
                            new LoupeAdapter(new LoupeHelper(new LoupeFactory(), \sys_get_temp_dir() . '/seal-test-indexes')),
                            $schema,
                        );
                    }

                    return self::$engine;
                }
            }

        .. code-block:: php

            <?php // tests/bootstrap.php

            require __DIR__ . '/../vendor/autoload.php';
            require __DIR__ . '/EngineHelper.php';

            // create your application's database here too, so both are ready together

            $task = App\Tests\EngineHelper::get()->createSchema(['return_slow_promise_result' => true]);
            $task?->wait();

        The ``wait()`` call blocks until the index really exists, which is only
        recommended in tests, as noted directly on ``TaskInterface``.

    .. group-tab:: Laravel / Symfony / Spiral / Mezzio / Yii

        The framework integrations ship a console command to create the indexes,
        the same command you would use to prepare a fresh environment. Run it
        once before the test suite, next to whatever creates your database
        (migrations, fixtures, ...):

        .. code-block:: bash

            # Laravel
            php artisan cmsig:seal:index-create

            # Symfony
            bin/console cmsig:seal:index-create

            # Spiral
            php app.php cmsig:seal:index-create

            # Mezzio
            vendor/bin/laminas cmsig:seal:index-create

            # Yii
            ./yii cmsig:seal:index-create

        This is exactly what the ``CommandTest`` of the Symfony example application
        does as part of its test suite, see
        `.examples/symfony/tests/CommandTest.php <https://github.com/php-cmsig/search/blob/0.12/.examples/symfony/tests/CommandTest.php>`__.

Index documents synchronously
------------------------------

Outside of tests, ``saveDocument()``, ``deleteDocument()`` and ``bulk()`` return
as soon as the write has been scheduled, without waiting for the search engine to
finish indexing it. Pass ``['return_slow_promise_result' => true]`` and call
``wait()`` on the returned task to make the call block until the document is
searchable, so the assertion right after it can rely on the data being there:

.. code-block:: php

    <?php

    declare(strict_types=1);

    namespace App\Tests;

    use PHPUnit\Framework\TestCase;

    final class BlogSearchTest extends TestCase
    {
        public function testSearchFindsIndexedDocument(): void
        {
            $engine = EngineHelper::get(); // or however your framework injects it

            $task = $engine->saveDocument('blog', [
                'id' => 'test-1',
                'title' => 'My first blog post',
                'tags' => ['UI', 'UX'],
            ], ['return_slow_promise_result' => true]);
            $task?->wait();

            $result = $engine->createSearchBuilder('blog')
                ->addFilter(\CmsIg\Seal\Search\Condition\Condition::search('first blog post'))
                ->getResult();

            $this->assertSame(1, $result->total());

            $task = $engine->deleteDocument('blog', 'test-1', ['return_slow_promise_result' => true]);
            $task?->wait();
        }
    }

Clean up documents after the test
------------------------------------

Every test that indexes documents should also remove them once it is done, the
same way every document saved above was deleted with ``deleteDocument()`` right
after the assertions. This keeps the index empty between tests so they stay
independent of each other and of the order they run in, and it is the pattern
used consistently throughout this project's own adapter test suites
(``CmsIg\Seal\Testing\AbstractSearcherTestCase`` and
``AbstractIndexerTestCase``, see the :doc:`create-own-adapter` cookbook
for the base classes themselves): index the fixtures at the start of the test,
run the assertions, then delete every document that was indexed before the test
ends.

If a test can fail before reaching its cleanup code, prefer deleting in
``tearDown()`` instead of at the end of the test method so the index is still
reset even when an assertion throws.
