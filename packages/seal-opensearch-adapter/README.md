> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

---

<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> Meilisearch Adapter</h1>

<br />
<br />

The `OpensearchAdapter` write the documents into an [Opensearch](https://github.com/opensearch-project/OpenSearch) server instance.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Usage

The following code shows how to create an Engine using this Adapter:

```php
<?php

use OpenSearch\ClientBuilder;
use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapter;
use CmsIg\Seal\Engine;

$client = ClientBuilder::create()->setHosts([
    '127.0.0.1:9200'
])->build()

$engine = new Engine(
    new OpensearchAdapter($client),
    $schema,
);
```

Via DSN for your favorite framework:

```env
opensearch://127.0.0.1:9200
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
