<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL</h1>

<div align="center">

<strong>Monorepository</strong> for **SEAL** a **S**earch **E**ngine **A**bstraction **L**ayer with support to different search engines<br/>
<a href="https://schranz-search.github.io/schranz-search/">Documentation</a> | [Packages](#-packages)

Elasticsearch | Opensearch | Meilisearch | Algolia | Loupe | Solr | Redisearch | Typesense <br/>
**PHP** | Symfony | Laravel | Spiral | Mezzio | Yii

</div>

<br />
<br />

## 👋 Introduction

The **SEAL** project is a PHP library designed to simplify the process of interacting
with different search engines. It provides a straightforward interface that enables users
to communicate with various search engines, including:

- [Elasticsearch](packages/seal-elasticsearch-adapter)
- [Opensearch](packages/seal-opensearch-adapter)
- [Meilisearch](packages/seal-meilisearch-adapter)
- [Algolia](packages/seal-algolia-adapter)
- [Loupe](packages/seal-loupe-adapter)
- [Solr](packages/seal-solr-adapter)
- [RediSearch](packages/seal-redisearch-adapter)
- [Typesense](packages/seal-typesense-adapter)
- [Memory](packages/seal-memory-adapter)
- ... missing something? Let us know!

It also provides integration packages for the

[Symfony](integrations/symfony),
[Laravel](integrations/laravel),
[Spiral](integrations/spiral),
[Mezzio](integrations/mezzio) 
and [Yii](integrations/yii) PHP frameworks.

It is worth noting that the project draws inspiration from the
``Doctrine`` and ``Flysystem`` projects. These two projects have been a great inspiration
in the development of SEAL, as they provide excellent examples of how to create consistent
and user-friendly APIs for complex systems.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## 🏗️ Structure

![SEAL Structure overview](docs/_images/overview.svg)


SEAL's provides a basic abstraction layer for add, remove and search and filters for documents.
The main class and service handling this is called `Engine`, which is responsible for all this things.
The `Schema` which is required defines the different `Indexes` and their `Fields`.

The project provides different `Adapters` which the Engine uses to communicate with the different `Search Engine` software and services.
This way it is easy to switch between different search engine software and services.

**Glossary**

| Term            | Definition                                                                                                                                                                                        |
|-----------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `Engine`        | The main class and service responsible to provide the basic interface for add, remove and search and filters for documents.                                                                       |
| `Schema`        | Defines the different `Indexes` and their `Fields`, for every field a specific type need to be defined and what you want todo with them via flags like `searchable`, `filterable` and `sortable`. |
| `Adapter`       | Provides the communication between the Engine and the Search Engine software and services.                                                                                                        |
| `Documents`     | A structure of data that you want to index need to follow the structure of the fields of the index schema.                                                                                        |
| `Search Engine` | Search Engine software or service where the data will actually be stored currently `Meilisearch`, `Opensearch`, `Elasticsearch`, `Algolia`, `Redisearch`, `Solr` and `Typesense` is supported.    |

## 📖 Installation and Documentation

The documentation is available at [https://schranz-search.github.io/schranz-search/](https://schranz-search.github.io/schranz-search/).
It is the recommended and best way to start using the library, it will step-by-step guide you through all the features
of the library.

- [Introduction](https://schranz-search.github.io/schranz-search/index.html)
- [Getting Started](https://schranz-search.github.io/schranz-search/getting-started/index.html)
- [Schema](https://schranz-search.github.io/schranz-search/schema/index.html)
- [Index Operations](https://schranz-search.github.io/schranz-search/indexing/index.html)
- [Search & Filters](https://schranz-search.github.io/schranz-search/search-and-filters/index.html)
- [Cookbooks](https://schranz-search.github.io/schranz-search/cookbooks/index.html)
- [Research](https://schranz-search.github.io/schranz-search/research/index.html)

## 📦 Packages

Full list of packages provided by the SEAL project:

- [`schranz-search/seal`](packages/seal/README.md) - The core package of the SEAL project.
- [`schranz-search/seal-algolia-adapter`](packages/seal-algolia-adapter/README.md) - Adapter for the Algolia search engine.
- [`schranz-search/seal-elasticsearch-adapter`](packages/seal-elasticsearch-adapter/README.md) - Adapter for the Elasticsearch search engine.
- [`schranz-search/seal-opensearch-adapter`](packages/seal-opensearch-adapter/README.md) - Adapter for the Opensearch search engine.
- [`schranz-search/seal-meilisearch-adapter`](packages/seal-meilisearch-adapter/README.md) - Adapter for the Meilisearch search engine.
- [`schranz-search/seal-loupe-adapter`](packages/seal-loupe-adapter/README.md) - Adapter for the Loupe search engine.
- [`schranz-search/seal-redisearch-adapter`](packages/seal-redisearch-adapter/README.md) - Adapter for the Redisearch search engine.
- [`schranz-search/seal-solr-adapter`](packages/seal-solr-adapter/README.md) - Adapter for the Solr search engine.
- [`schranz-search/seal-typesense-adapter`](packages/seal-typesense-adapter/README.md) - Adapter for the Typesense search engine.
- [`schranz-search/seal-read-write-adapter`](packages/seal-read-write-adapter/README.md) - Adapter to split read and write operations.
- [`schranz-search/seal-multi-adapter`](packages/seal-multi-adapter/README.md) - Adapter to write into multiple search engines.
- [`schranz-search/laravel-package`](integrations/laravel/README.md) - Integrates SEAL into the Laravel framework.
- [`schranz-search/symfony-bundle`](integrations/symfony/README.md) - Integrates SEAL into the Symfony framework.
- [`schranz-search/spiral-bridge`](integrations/spiral/README.md) - Integrates SEAL into the Spiral framework.
- [`schranz-search/mezzio-module`](integrations/mezzio/README.md) - Integrates SEAL into the Mezzio framework.
- [`schranz-search/yii-module`](integrations/yii/README.md) - Integrates SEAL into the Yii framework.

Have also a look at the following tags:

- [https://packagist.org/search/?tags=seal-adapter](https://packagist.org/search/?tags=seal-adapter)
- [https://github.com/topics/seal-php-adapter](https://github.com/topics/seal-php-adapter)

## 🦑 Similar Projects

Following projects in the past target similar problem:

- [https://github.com/nresni/Ariadne](https://github.com/nresni/Ariadne) (Solr, Elasticsearch, Zendsearch: outdated 12 years ago)
- [https://github.com/massiveart/MassiveSearchBundle](https://github.com/massiveart/MassiveSearchBundle) (ZendSearch, Elasticsearch)
- [https://github.com/laravel/scout](https://github.com/laravel/scout) (Algolia, Meilisearch)

## 📩 Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/schranz-search/schranz-search/graphs/contributors)
