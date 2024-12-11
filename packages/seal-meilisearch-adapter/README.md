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

The `MeilisearchAdapter` write the documents into a [Meilisearch](https://github.com/meilisearch/meilisearch) server instance.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-meilisearch-adapter
```

## Usage.

The following code shows how to create an Engine using this Adapter:

```php
<?php

use Meilisearch\Client;
use CmsIg\Seal\Adapter\Meilisearch\MeilisearchAdapter;
use CmsIg\Seal\Engine;

$client = new Client('http://127.0.0.1:7700');

$engine = new Engine(
    new MeilisearchAdapter($client),
    $schema,
);
```

Via DSN for your favorite framework:

```env
meilisearch://127.0.0.1:7700
meilisearch://apiKey@127.0.0.1:7700
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
