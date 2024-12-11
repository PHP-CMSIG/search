> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

---

<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> RediSearch Adapter</h1>

<br />
<br />

The `RediSearchAdapter` write the documents into a [RediSearch](https://redis.io/docs/stack/search/) server instance. The Redis Server requires to run with the RedisSearch and JSON module.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-redisearch-adapter
```

## Usage.

The following code shows how to create an Engine using this Adapter:

```php
<?php

use Redis;
use CmsIg\Seal\Adapter\RediSearch\RediSearchAdapter;
use CmsIg\Seal\Engine;

$redis = new Redis([
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => ['phpredis', 'phpredis'],
]);

$engine = new Engine(
    new RediSearchAdapter($redis),
    $schema,
);
```

Via DSN for your favorite framework:

```env
redis://127.0.0.1:6379
redis://supersecure@127.0.0.1:6379
redis://phpredis:phpredis@127.0.0.1:6379
```

The `ext-redis` and `ext-json` PHP extension is required for this adapter.
The `Redisearch` and `RedisJson` module is required for the Redis Server.

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
