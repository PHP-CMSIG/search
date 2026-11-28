Use cases
=========

SEAL is used by data-processing frameworks and content management systems that
need one API for multiple search engines.

Flow PHP
--------

`Flow PHP <https://flow-php.com/>`__ uses the ``flow-php/etl-adapter-seal``
package to write rows from its data pipelines to search indexes. Flow replaced
its dedicated Elasticsearch adapter with SEAL so the same pipeline can target
Elasticsearch, Opensearch, Meilisearch, Solr, Typesense, Algolia, RediSearch,
or Loupe.

- `SEAL DSL reference in Flow PHP <https://flow-php.com/documentation/dsl/seal/>`__
- `Flow PHP migration guide <https://flow-php.com/documentation/upgrading/#removal-of-elasticsearch-adapter>`__

Content management systems
--------------------------

Contao CMS
~~~~~~~~~~

`Contao CMS <https://contao.org/>`__ uses SEAL for its back end search.
Administrators can select a search engine through a DSN, while the
``contao/loupe-bridge`` package provides a local option that does not require a
separate search server.

See the `Contao back end search documentation <https://docs.contao.org/5.x/manual/en/installation/system-requirements/backend-search/>`__.

Sulu CMS
~~~~~~~~

`Sulu CMS <https://sulu.io/>`__ uses the SEAL bundle for search in both its
administration interface and websites since Sulu 3.0.

See the `Sulu search documentation <https://docs.sulu.io/en/3.0/cookbook/using-elasticsearch.html>`__.

TYPO3
~~~~~

The `TYPO3 SEAL extension <https://github.com/lochmueller/seal>`__ connects
TYPO3's indexing system to SEAL. It provides front end search, autocomplete,
schema management, and DSN-based selection of search engine adapters.

Are you using SEAL? Share your project and setup in the
`SEAL usage discussion <https://github.com/PHP-CMSIG/search/discussions/416>`__.
