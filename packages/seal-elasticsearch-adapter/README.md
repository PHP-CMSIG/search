<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> Elasticsearch Adapter</h1>

<br />
<br />

The `ElasticsearchAdapter` write the documents into an [Elasticsearch](https://github.com/elastic/elasticsearch) server instance.

> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

# Schranz Search SEAL Elasticsearch Adapter

The `ElasticsearchAdapter` write the documents into an Elasticsearch server instance.

> This is a subtree split of the `php-cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-elasticsearch-adapter
```

## Usage

The following code shows how to create an Engine using this Adapter:

```php
<?php

use Elastic\Elasticsearch\ClientBuilder;
use CmsIg\Seal\Adapter\Elasticsearch\ElasticsearchAdapter;
use CmsIg\Seal\Engine;

$client = ClientBuilder::create()->setHosts([
    '127.0.0.1:9200'
])->build()

$engine = new Engine(
    new ElasticsearchAdapter($client),
    $schema,
);
```

Via DSN for your favorite framework:

```env
elasticsearch://127.0.0.1:9200
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
