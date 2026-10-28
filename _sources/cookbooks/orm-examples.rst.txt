ORM Examples
============

Doctrine ORM
------------

It is very common to use an ORM like Doctrine to manage your business objects.
This cookbook will show you how to connect Doctrine ORM lifecycle events
to automatically update your Search Engine indexes.

Create a ReindexProvider
~~~~~~~~~~~~~~~~~~~~~~~~

At first, a ``ReindexProvider`` is required for the newly created index.

.. note::

    See :ref:`reindex-operations` Documentation for more information about creating a reindex provider
    and how to call it in the different frameworks.

    Make also sure you are familiar with basics of SEAL, see :doc:`../getting-started/index`.

.. code-block:: php

    <?php

    class BlogReindexProvider implements ReindexProviderInterface
    {
        public function __construct(private BlogRepository $entityRepository)
        {}

        public static function getIndex(): string
        {
            return 'blog';
        }

        public function total(): ?int
        {
            return $this->entityRepository->count([]);
        }

        public function provide(ReindexConfig $reindexConfig): \Generator
        {
            $entities = $this->loadEntities(ReindexConfig $reindexConfig);

            foreach ($entities as $entity) {
                // map entity to your index structure
                // keep best practices in mind your index structure should be optimized for search
                // and not represent the same domain / entity model as in your ORM
                yield [
                    'id' => (string) $entity->getId(),
                    'title' => $entity->getTitle(),
                    'content' => [
                        $entity->getIntroduction(),
                        $entity->getDescription(),
                    ],
                ];
            }
        }

        /**
         * @return \Generator<Blog>
         */
        private function loadEntities(ReindexConfig $reindexConfig): \Generator
        {
            $queryBuilder = $this->entityRepository->createQueryBuilder('entity');

            $identifiers = $reindexConfig->getIdentifiers();

            if ($identifiers !== []) { // for partial updates by our doctrine listener
                $queryBuilder
                    ->andWhere('entity.id IN (:ids)')
                    ->setParameter('ids', \array_map('intval', $identifiers));
            }

            return $queryBuilder->getQuery()->toIterable();
        }
    }

Create a DoctrineListener
~~~~~~~~~~~~~~~~~~~~~~~~~

Now create a ``Doctrine Listener`` which listens to the ``lifecycle events`` of your entity.
We are interested in newly created, modified and deleted entities.

We collect all entities in an array and on ``postFlush`` we trigger the reindex operation.

This will call our previously created ``ReindexProvider`` to update the index.

.. code-block:: php

    <?php

    class BlogSearchDoctrineListener implements ResetInterface
    {
        /**
         * @var Blog[]
         */
        private array $entities = [];

        public function __construct(
            private EngineInterface $engine,
            private iterable $reindexProviders,
        ) {}

        public function preRemove(LifecycleEventArgs $eventArgs): void
        {
            $this->entities[] = $eventArgs->getObject();
        }

        public function preUpdate(LifecycleEventArgs $eventArgs): void
        {
            $this->entities[] = $eventArgs->getObject();
        }

        public function prePersist(LifecycleEventArgs $eventArgs): void
        {
            $this->entities[] = $eventArgs->getObject();
        }

        public function postFlush(PostFlushEventArgs $args): void
        {
            if ($this->entities === []) {
                return;
            }

            $reindexConfig = new ReindexConfig()
                ->withIndex('blog')
                ->withIdentifiers(\array_map(fn(Blog $entity) => (string) $entity->getId(), $this->entities));

            // The following will update the index synchronously.
            // If you want todo it asynchronously you can use ``Symfony Messenger`` or any queue system
            // to create an async message/command and handler and do the following line in the async handler.
            $engine->reindex($reindexProviders, $reindexConfig);

            $this->reset();
        }

        public function onClear(OnClearEventArgs $args): void
        {
            $this->reset();
        }

        public function reset(): void
        {
            $this->entities = [];
        }
    }

Depending on your used framework it is required to register the above ``DoctrineListener`` as a service
correctly via tags or configuration. See your framework doctrine integration documentation for it.
