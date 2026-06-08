> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

---

<div align="center">
    <sup>
        <b>Your feedback is important 📘</b> <br />
        <a href="https://github.com/PHP-CMSIG/search/discussions/416">Are you working with SEAL? Let us know!</a>
        | 
        <a href="https://github.com/PHP-CMSIG/search/discussions/457">Which Search Engines do you use and why?</a>
    </sup>
</div>

<br />
<br />
<br />
<br />

<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> OpenSearch Adapter</h1>

<br />
<br />

The `OpensearchAdapter` write the documents into an [Opensearch](https://github.com/opensearch-project/OpenSearch) server instance.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Install the adapter:

```bash
composer require cmsig/seal-opensearch-adapter
```

## Usage

### Via DSN

When you use the adapter via DSN, `opensearch-php` uses discovery to find the installed PSR implementations automatically.

Your project needs:

- a `psr/http-client-implementation`
- a `psr/http-factory-implementation`

If your framework or application already provides them, no additional HTTP client package is needed.

Example DSNs:

```env
opensearch://127.0.0.1:9200
opensearch://127.0.0.1:9200?tls=true
opensearch://username:password@127.0.0.1:9200?tls=true
```

Example standalone usage via DSN:

```php
<?php

use CmsIg\Seal\Adapter\AdapterFactory;
use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapterFactory;
use CmsIg\Seal\Engine;

$adapterFactory = new AdapterFactory([
    OpensearchAdapterFactory::getName() => new OpensearchAdapterFactory(),
]);

$engine = new Engine(
    $adapterFactory->createAdapter('opensearch://127.0.0.1:9200?tls=true'),
    $schema,
);
```

### Manual client creation

If you do not use the adapter via DSN, create an `OpenSearch\Client` yourself and pass it to the adapter.

For example, you can use one of these PSR-18 / PSR-17 stacks:

Symfony:

```bash
composer require symfony/http-client nyholm/psr7
```

Guzzle:

```bash
composer require guzzlehttp/guzzle guzzlehttp/psr7
```

The following code shows how to create an `Engine` using this adapter:

```php
<?php

use CmsIg\Seal\Adapter\Opensearch\OpensearchAdapter;
use CmsIg\Seal\Engine;
use OpenSearch\Client;

/** @var Client $client */
// Manual client creation example.
//
// Symfony example:
// composer require symfony/http-client nyholm/psr7
// use OpenSearch\SymfonyClientFactory;
// $client = (new SymfonyClientFactory())->create([
//     'base_uri' => 'http://127.0.0.1:9200',
// ]);
//
// Guzzle example:
// composer require guzzlehttp/guzzle guzzlehttp/psr7
// use OpenSearch\GuzzleClientFactory;
// $client = (new GuzzleClientFactory())->create([
//     'base_uri' => 'http://127.0.0.1:9200',
// ]);

$engine = new Engine(
    new OpensearchAdapter($client),
    $schema,
);
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
