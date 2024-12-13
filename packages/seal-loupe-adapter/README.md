> **Note**:
> This is part of the `cmsig/search` project create issues in the [main repository](https://github.com/php-cmsig/search).

---

<div align="center">
    <img alt="SEAL Logo with an abstract seal sitting on a telescope." src="https://avatars.githubusercontent.com/u/120221538?s=400&v=6" width="200" height="200">
</div>

<div align="center">Logo created by <a href="https://cargocollective.com/meinewilma">Meine Wilma</a></div>

<h1 align="center">SEAL <br /> Loupe Adapter</h1>

<br />
<br />

The `LoupeAdapter` write the documents into a [Loupe](https://github.com/loupe-php/loupe) SQLite instance.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-loupe-adapter
```

## Usage.

The following code shows how to create an Engine using this Adapter:

```php
<?php

use Loupe\Loupe\LoupeFactory;
use CmsIg\Seal\Adapter\Loupe\LoupeAdapter;
use CmsIg\Seal\Adapter\Loupe\LoupeHelper;
use CmsIg\Seal\Engine;

$loupeFactory = new LoupeFactory();

$engine = new Engine(
    new LoupeAdapter(new LoupeHelper($loupeFactory, 'var/indexes/')),
    $schema,
);
```

Via DSN for your favorite framework:

```env
loupe://var/indexes/
loupe://
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
