<?php

declare(strict_types=1);

namespace App\Web\Shared\Layout\Main;

use Yiisoft\Assets\AssetBundle;

final class MainAsset extends AssetBundle
{
    public string|null $basePath = '@assets/main';
    public string|null $baseUrl = '@assetsUrl/main';
    public string|null $sourcePath = '@assetsSource/main';

    public array $css = [
        'site.css',
    ];
}
