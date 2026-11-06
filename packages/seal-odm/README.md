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

<h1 align="center">SEAL <br /> ODM</h1>

<br />
<br />

The `ODM` (object data mapper) maps the documents to PHP objects.

> **Note**:
> This project is heavily under development and any feedback is greatly appreciated.

## Installation

Use [composer](https://getcomposer.org/) for install the package:

```bash
composer require cmsig/seal cmsig/seal-odm
```

## Usage

Define your schema metadata directly on PHP classes:

```php
<?php

use CmsIg\Seal\Odm\Attribute\Field;
use CmsIg\Seal\Odm\Attribute\Identifier;
use CmsIg\Seal\Odm\Attribute\Index;

#[Index(name: 'blog')]
final class BlogDocument
{
    #[Identifier]
    public string $id;

    #[Field(sortable: true)]
    public string $title;

    /**
     * @var list<string>
     */
    #[Field(filterable: true, facet: true)]
    public array $tags;
}
```

Identifiers are not sortable by default. Enable sorting and pass adapter options with
`#[Identifier(sortable: true, options: ['customKey' => 'customValue'])]`.

Load the schema from a directory containing annotated classes:

```php
<?php

use CmsIg\Seal\Engine;
use CmsIg\Seal\Odm\Mapper\OdmDataMapper;
use CmsIg\Seal\Odm\OdmEngine;
use CmsIg\Seal\Odm\Schema\Loader\AttributeLoader;

$schema = (new AttributeLoader([__DIR__ . '/src/Search']))->load();
$engine = new Engine($adapter, $schema);
$odmEngine = new OdmEngine($engine, new OdmDataMapper($schema));
```

Save objects directly; use the document class for reads, bulk operations, and index management:

```php
$blog = new BlogDocument();
$blog->id = '1';
$blog->title = 'My first post';
$blog->tags = [];

$odmEngine->saveDocument($blog);
$blog = $odmEngine->getDocument(BlogDocument::class, '1');
$odmEngine->bulk(BlogDocument::class, [$blog], ['2']);
$odmEngine->deleteDocument(BlogDocument::class, '1');
$odmEngine->countDocuments(BlogDocument::class);

$result = $odmEngine->createSearchBuilder(BlogDocument::class)->getResult();
foreach ($result as $blog) {
    echo $blog->title;
}

$odmEngine->createIndex(BlogDocument::class);
$odmEngine->updateIndex(BlogDocument::class);
$odmEngine->existIndex(BlogDocument::class);
$odmEngine->dropIndex(BlogDocument::class);
```

Each document class must map to exactly one index in the loaded schema. Unknown or ambiguous
class mappings throw an exception. `getDocument()` and search results preserve the requested
class through PHPDoc generics for IDEs and static analysis. Calling `index(OtherDocument::class)`
on a search builder returns a new builder for that class, preserving the original builder.
Schema-wide operations still apply to all indexes, and reindex providers still declare index names.

Supported inferred field types are `string`, `int`, `float`, `bool`, `DateTimeInterface` implementations,
object types as nested `ObjectField`s, and `array`/`list` properties with element types declared in PHPDoc.

The `OdmDataMapper` creates objects without calling constructors and reads or writes mapped values directly
through reflected properties. To preserve the metadata required for that behavior, build the schema with
`AttributeLoader` and pass the same `Schema` instance to both `Engine` and `OdmDataMapper`.

The ODM metadata uses `mappedClass` consistently for document, nested object, and date classes:

```php
options: ['odm' => ['mappedClass' => BlogDocument::class]]
```

## Authors

- [Alexander Schranz](https://github.com/alexander-schranz/)
- [The Community Contributors](https://github.com/php-cmsig/search/graphs/contributors)
