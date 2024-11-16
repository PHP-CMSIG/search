<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> Typesense Adapter</h1>

<br />
<br />

The `TypesenseAdapter` write the documents into a [Typesense](https://github.com/typesense/typesense) server instance.

> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-typesense-adapter
```

## Usage.

The following code shows how to create an Engine using this Adapter:

```php
<?php

use Http\Client\Curl\Client as CurlClient;
use Http\Discovery\Psr17FactoryDiscovery;
use CmsIg\Seal\Adapter\Typesense\TypesenseAdapter;
use CmsIg\Seal\Engine;
use Typesense\Client;

$client = new Client(
    [
        'api_key' => 'S3CR3T',
        'nodes' => [
            [
                'host' => '127.0.0.1',
                'port' => '8108',
                'protocol' => 'http',
            ],
        ],
        'client' => new CurlClient(Psr17FactoryDiscovery::findResponseFactory(), Psr17FactoryDiscovery::findStreamFactory()),
    ]
);

$engine = new Engine(
    new TypesenseAdapter($client),
    $schema,
);
```

Via DSN for your favorite framework:

```env
typesense://S3CR3T@127.0.0.1:8108
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
