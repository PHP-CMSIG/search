<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> Memory Adapter</h1>

<br />
<br />

The `MemoryAdapter` write the documents into an in-memory array.

> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-memory-adapter
```

## Usage

It is mostly used for testing purposes and as a reference implementation.

The following code shows how to create an Engine using this Adapter:

```php
<?php

use CmsIg\Seal\Adapter\Memory\MemoryAdapter;
use CmsIg\Seal\Engine;

$engine = new Engine(
    new MemoryAdapter(),
    $schema,
);
```

Via DSN for your favorite framework:

```env
memory://
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
