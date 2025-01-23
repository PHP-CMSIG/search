Research
========

This project started as a research project to find out how to create a common interface for different search engines.
In this document we collect all the information we found out during our research. Feel free to add all kind of
interesting information you want to share.

List of Search Engines
----------------------

Here we collect different search engines which are around and could be interesting:

- `Elasticsearch <#elasticsearch>`__ - ``cmsig/seal-elasticsearch-adapter``
- `Opensearch <#opensearch>`__ - ``cmsig/seal-opensearch-adapter``
- `Meilisearch <#meilisearch>`__ - ``cmsig/seal-meilisearch-adapter``
- `Algolia <#algolia>`__ - ``cmsig/seal-algolia-adapter``
- `Solr <#solr>`__ - ``cmsig/seal-solr-adapter``
- `RediSearch <#redisearch>`__ - ``cmsig/seal-redisearch-adapter``
- `Typesense <#typesense>`__ - ``cmsig/seal-typesense-adapter``
- `Loupe <#loupe>`__ - ``cmsig/seal-loupe-adapter``
- `Zinc Labs <#zinc-labs>`__ (work in progress `#79 <https://github.com/php-cmsig/search/pull/79>`__)
- `Manticore Search <#manticore-search>`__ (work in progress `#103 <https://github.com/php-cmsig/search/pull/103>`__)
- `ZendSearch <#zendsearch>`__
- `Kailua Labs <#kailua-labs>`__
- `TnTSearch <#tntsearch>`__
- `Sonic <#sonic>`__
- `Vespa <#vespa>`__
- `Toshi <#toshi>`__
- `Quickwit <#quickwit>`__
- `nrtSearch <#nrtsearch>`__
- `MongoDB Atlas <#mongodb-atlas>`__
- `PostgreSQL Full Text Search <#postgresql-full-text-search>`__
- `MySQL Full Text Search <#mysql-full-text-search>`__
- `Sphinx Search <#sphinx-search>`__
- `Search.io <#searchio>`__
- `Azure Cognitive Search <#azure-cognitive-search>`__
- `Google Cloud Search <#google-cloud-search>`__
- `Amazon CloudSearch <#amazon-cloudsearch>`__
- `Gigablast <#gigablast>`__
- `Fess <#fess>`__
- `Bleve <#bleve>`__
- `Qdrant <#qdrant>`__
- `OpenAI <#openai>`__
- `Jina <#jina>`__
- `Paradedb <#paradedb>`__

Some more research links:
-------------------------

- `https://alternativeto.net/software/meilisearch/ <https://alternativeto.net/software/meilisearch/>`__
- `https://github.com/awesome-selfhosted/awesome-selfhosted#search-engines <https://github.com/awesome-selfhosted/awesome-selfhosted#search-engines>`__
- `https://help.openai.com/en/articles/6272952-search-transition-guide <https://help.openai.com/en/articles/6272952-search-transition-guide>`__
- `https://www.reddit.com/r/PHP/comments/104278m/research_what_search_services_engines_do_you_use/ <https://www.reddit.com/r/PHP/comments/104278m/research_what_search_services_engines_do_you_use/>`__
- `https://github.com/doofinder/php-doofinder <https://github.com/doofinder/php-doofinder>`__
- `https://www.athenasearch.io/ <https://www.athenasearch.io/>`__
- `https://www.g2.com/products/addsearch-site-search/reviews <https://www.g2.com/products/addsearch-site-search/reviews>`__
- `https://aws.amazon.com/de/athena/ <https://aws.amazon.com/de/athena/>`__ / `https://twitter.com/dr4goonis/status/1628451049013972993 <https://twitter.com/dr4goonis/status/1628451049013972993>`__
- `https://milvus.io/ <https://milvus.io/>`__ / `https://twitter.com/milvusio <https://twitter.com/milvusio>`__ / `https://packagist.org/packages/kaycn/milvusphp <https://packagist.org/packages/kaycn/milvusphp>`__
- `https://github.com/pgvector/pgvector <https://github.com/pgvector/pgvector>`__
- `https://vald.vdaas.org/ <https://vald.vdaas.org/>`__
- `https://solr.apache.org/guide/solr/latest/query-guide/dense-vector-search.html <https://solr.apache.org/guide/solr/latest/query-guide/dense-vector-search.html>`__
- `https://github.com/facebookresearch/faiss <https://github.com/facebookresearch/faiss>`__
- `https://blog.vespa.ai/billion-scale-knn/ <https://blog.vespa.ai/billion-scale-knn/>`__

UI/UX related links:
--------------------

- `https://design4users.com/design-search-in-user-interfaces/ <https://design4users.com/design-search-in-user-interfaces/>`__

Optimization links:
-------------------

- `https://sites.google.com/site/kevinbouge/stopwords-lists <https://sites.google.com/site/kevinbouge/stopwords-lists>`__
- `https://github.com/uschindler/german-decompounder <https://github.com/uschindler/german-decompounder>`__
- `https://symfony.com/blog/migrating-symfony-com-search-engine-to-meilisearch <https://symfony.com/blog/migrating-symfony-com-search-engine-to-meilisearch>`__
- `https://thetizzo.com/2024/02/13/scaling-elasticsearch-for-fun-and-profit <https://thetizzo.com/2024/02/13/scaling-elasticsearch-for-fun-and-profit>`__

Descriptions of Search Engines
------------------------------

Elasticsearch
~~~~~~~~~~~~~

Widely used search based on Java.

- Server: `Elasticsearch Server <https://github.com/elastic/elasticsearch>`__
- PHP Client: `Elasticsearch PHP <https://github.com/elastic/elasticsearch-php>`__

Implementation: `cmsig/seal-elasticsearch-adapter <https://github.com/php-cmsig/seal-elasticsearch-adapter>`__

Opensearch
~~~~~~~~~~

Fork of Elasticsearch also written in Java.

- Server: `Opensearch Server <https://github.com/opensearch-project/OpenSearch>`__
- PHP Client: `Opensearch PHP <https://github.com/opensearch-project/opensearch-php>`__

Implementation: `cmsig/seal-opensearch-adapter <https://github.com/php-cmsig/seal-opensearch-adapter>`__

Meilisearch
~~~~~~~~~~~

A search engine written in Rust:

- Server: `MeiliSearch Server <https://github.com/meilisearch/meilisearch>`__
- PHP Client: `MeiliSearch PHP <https://github.com/meilisearch/meilisearch-php>`__

Implementation: `cmsig/seal-meilisearch-adapter <https://github.com/php-cmsig/seal-meilisearch-adapter>`__

Algolia
~~~~~~~

Is a search as SaaS provided via Rest APIs and SDKs:

- Server: No server only Saas `https://www.algolia.com/ <https://www.algolia.com/>`__
- PHP Client: `Algolia PHP <https://github.com/algolia/algoliasearch-client-php>`__

Implementation: `cmsig/seal-algolia-adapter <https://github.com/php-cmsig/seal-algolia-adapter>`__

Solr
~~~~

A search engine under the Apache Project based on Lucene written in Java:

- Server: `Solr Server <https://github.com/apache/solr>`__
- PHP Client: `Solarium PHP <https://github.com/solariumphp/solarium>`__ seems to be a well maintained Client

Implementation: `cmsig/seal-solr-adapter <https://github.com/php-cmsig/seal-solr-adapter>`__

RediSearch
~~~~~~~~~~

A search out of the house of the redis labs.

- Server: `RediSearch Server <https://github.com/RediSearch/RediSearch>`__
- PHP Client: `Unofficial RediSearch PHP <https://github.com/MacFJA/php-redisearch>`__

Implementation: `cmsig/seal-redisearch-adapter <https://github.com/php-cmsig/seal-redisearch-adapter>`__

Typesense
~~~~~~~~~

Describes itself as a alternative to Algolia and Elasticsearch written in C++.

- Server: `Typesense Server <https://github.com/typesense/typesense>`__
- PHP Client: `Typesense PHP <https://github.com/typesense/typesense-php>`__

Implementation: `cmsig/seal-typesense-adapter <https://github.com/php-cmsig/seal-typesense-adapter>`__

Loupe
~~~~~

An SQLite based, PHP-only fulltext search engine.

- Implementation: `Loupe PHP <https://github.com/loupe-php/loupe>`__

Zinc Labs
~~~~~~~~~

Zinc search describes itself as a lightweight alternative to Elasticsearch written in GoLang.

- Server: `Zinclabs Server <https://github.com/zinclabs/zinc>`__
- PHP Client: No PHP SDK currently: `https://github.com/zinclabs/zinc/issues/12 <https://github.com/zinclabs/zinc/issues/12>`__

Implementation: work in progress `#79 <https://github.com/php-cmsig/search/pull/79>`__

Manticore Search
~~~~~~~~~~~~~~~~

Fork of Sphinx 2.3.2 in 2017, describes itself as an easy to use open source fast database for search.
Good alternative for Elasticsearch.

- Server: `Manticore Search Server <https://github.com/manticoresoftware/manticoresearch>`__
- PHP Client: `Manticore Search PHP Client <https://github.com/manticoresoftware/manticoresearch-php>`__

Implementation: work in progress `#103 <https://github.com/php-cmsig/search/pull/103>`__

ZendSearch
~~~~~~~~~~

A complete in PHP written implementation of the Lucene index. Not longer maintained:

- Implementation: `Zendsearch Implementation <https://github.com/handcraftedinthealps/zendsearch>`__

Kailua Labs
~~~~~~~~~~~

Next-gen search made simple:

- Server: No server only SaaS `https://www.kailualabs.com/ <https://www.kailualabs.com/>`__

TnTSearch
~~~~~~~~~~

Another implementation of a Search index written in PHP. Not based on Lucene.

- Implementation: `TntSearch Implementation <https://github.com/teamtnt/tntsearch>`__

Sonic
~~~~~

Describe itself as lightweight & schema-less search backend, an alternative to Elasticsearch that runs on a few MBs of RAM.

- Server: `Sonic Server <https://github.com/valeriansaliou/sonic>`__
- PHP Client: `Unofficial PHP Sonic <https://github.com/php-sonic/php-sonic>`_ looks outdated and not well maintained

Vespa
~~~~~

Describe itself as the open big data serving engine - Store, search, organize and make machine-learned inferences over big data at serving time.

- Server: `Vespa Server <https://github.com/vespa-engine/vespa>`__
- PHP Client: No client available only API based

Toshi
~~~~~

A full-text search engine in rust. Toshi strives to be to Elasticsearch what `Tantivy Server <https://github.com/quickwit-oss/tantivy>`_ is to Lucene:

- Server: `Toshi Server <https://github.com/toshi-search/Toshi>`__
- PHP Client: No client available only API based

Quickwit
~~~~~~~~

Describe itself as a cloud-native search engine for log management & analytics written in Rust. It is designed to be very cost-effective, easy to operate, and scale to petabytes.

- Server: `Quickwit Server <https://github.com/quickwit-oss/quickwit>`__
- PHP Client: No client available only API based

nrtSearch
~~~~~~~~~

Describe itself as a high performance gRPC server, with optional REST APIs on top of Apache Lucene version 8.x source, exposing Lucene's core functionality over a simple gRPC based API.

- Server: `nrtSearch Server <https://github.com/Yelp/nrtsearch>`__
- PHP Client: No client available only API based

MongoDB Atlas
~~~~~~~~~~~~~

None open source search engine from MongoDB. It is a cloud based search engine.

- Server: `MongoDB Atlas <https://www.mongodb.com/atlas/search>`__
- PHP Client: `MongoDB Atlas PHP Client <https://www.mongodb.com/docs/drivers/php/#connect-to-mongodb-atlas>`__

PostgreSQL Full Text Search
~~~~~~~~~~~~~~~~~~~~~~~~~~~

- Server: `PostgreSQL Server <https://www.postgresql.org/>`__
- PHP Client: No client use the `Full Text Feature <https://www.postgresql.org/docs/current/textsearch.html>`__ the Database connection.

MySQL Full Text Search
~~~~~~~~~~~~~~~~~~~~~~

- Server: `MySQL Server <https://dev.mysql.com/>`__
- PHP Client: No client use the `Full Text Feature <https://dev.mysql.com/doc/refman/8.0/en/fulltext-search.html>`__ the Database connection.

Sphinx Search
~~~~~~~~~~~~~

An older search engine written in Python.

- Server: `Sphinx Search Server <http://sphinxsearch.com/downloads/current/>`__
- PHP Client: No official client available

Search.io
~~~~~~~~~~

A SaaS search engine, In the past they used the name for Sajari Site Search.
Lately `acquired by Algolia <https://twitter.com/SearchioHQ/status/1569298045959020549>`_.

- Server: No server only Saas `Search.io Server <https://search.io/>`__
- PHP Client: `Official Search.io SDK for PHP <https://github.com/sajari/sdk-php>`__

Azure Cognitive Search
~~~~~~~~~~~~~~~~~~~~~~

A cloud based search from Microsoft Azure:

- Server: No server only SaaS `Azure Cognitive Search <https://learn.microsoft.com/en-us/azure/search/>`__
- PHP Client: No client available only `REST API <https://learn.microsoft.com/en-us/azure/search/search-get-started-rest>`__

Google Cloud Search
~~~~~~~~~~~~~~~~~~~

A cloud based search from Google:

- Server: No server only SaaS `Google Cloud Search <https://workspace.google.com/products/cloud-search/>`__
- PHP Client: No client available only `REST API <https://developers.google.com/cloud-search/docs/reference/rest>`__

Amazon CloudSearch
~~~~~~~~~~~~~~~~~~

A cloud based search from Amazon:

- Server: No server only SaaS `Amazon CloudSearch <https://aws.amazon.com/de/cloudsearch/>`__
- PHP Client: No client available only `REST API <https://docs.aws.amazon.com/aws-sdk-php/v2/guide/service-cloudsearch.html>`__

Gigablast
~~~~~~~~~

Describe itself as an open source web and enterprise search engine and spider/crawler
written in C++.

- Server: `Gigablast Server <https://github.com/gigablast/open-source-search-engine>`__
- PHP Client: No client available only `REST API <https://gigablast.com/api.html>`__

Fess
~~~~

Fess is very powerful and easily deployable Enterprise Search Server.

- Server: `Fess Server <https://github.com/codelibs/fess>`__

Bleve
~~~~~

A modern text ndexing in go, supported and sponsored by Couchbase:

- Library only: `Bleve <https://github.com/blevesearch/bleve>`__

Qdrant
~~~~~~

A vector AI based search database:

- Server: `Qdrant Server <https://github.com/qdrant/qdrant>`__
- PHP Client: No client available only `REST API <https://qdrant.github.io/qdrant/redoc/index.html>`__

OpenAI
~~~~~~

OpenAi embeddings can also be used to create search engine:

- Docs Embeddings: `Embeddings <https://beta.openai.com/docs/api-reference/embeddings>`__
- Docs
  Search: `Deprecated Search Migratin Transition <https://help.openai.com/en/articles/6272952-search-transition-guide>`__

Jina
~~~~

Another vector based search engine:

- Server: `Jina Server <https://github.com/jina-ai/jina/>`__

Paradedb
~~~~~~~~

A search and analytics engine ontop of Postgres, with own Postgres extensions written in Rust:

- Server: `Paradedb Server <https://github.com/paradedb/paradedb>`__
