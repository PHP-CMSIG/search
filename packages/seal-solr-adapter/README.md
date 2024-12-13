> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

---

<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> Solr Adapter</h1>

<br />
<br />

The `SolrAdapter` write the documents into a [Apache Solr](https://github.com/apache/solr) server instance. The Apache Solr server is running in the [`cloud mode`](https://solr.apache.org/guide/solr/latest/getting-started/tutorial-solrcloud.html) as we require to use collections for indexes.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-solr-adapter
```

## Usage.

The following code shows how to create an Engine using this Adapter:

```php
<?php

use Solr\Client;
use Solarium\Core\Client\Adapter\Curl;
use CmsIg\Seal\Adapter\Solr\SolrAdapter;
use CmsIg\Seal\Engine;
use Symfony\Component\EventDispatcher\EventDispatcher;

$client = new Client(new Curl(), new EventDispatcher(), [
    'endpoint' => [
        'localhost' => [
            'host' => '127.0.0.1',
            'port' => '8983',
            // authenticated required for configset api https://solr.apache.org/guide/8_9/configsets-api.html
            // alternative set solr.disableConfigSetsCreateAuthChecks=true in your server setup
            'username' => 'solr',
            'password' => 'SolrRocks',
        ],
    ]
]);

$engine = new Engine(
    new SolrAdapter($client),
    $schema,
);
```

Via DSN for your favorite framework:

```env
solr://127.0.0.1:8983
solr://solr:SolrRocks@127.0.0.1:8983
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
