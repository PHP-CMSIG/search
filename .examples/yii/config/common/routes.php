<?php

declare(strict_types=1);

use App\Web\Search\SearchController;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()
        ->routes(
            Route::get('/')->action([SearchController::class, 'home'])->name('home'),
            Route::get('/algolia')->action([SearchController::class, 'algolia'])->name('algolia'),
            Route::get('/elasticsearch')->action([SearchController::class, 'elasticsearch'])->name('elasticsearch'),
            Route::get('/loupe')->action([SearchController::class, 'loupe'])->name('loupe'),
            Route::get('/meilisearch')->action([SearchController::class, 'meilisearch'])->name('meilisearch'),
            Route::get('/memory')->action([SearchController::class, 'memory'])->name('Memory'),
            Route::get('/opensearch')->action([SearchController::class, 'opensearch'])->name('opensearch'),
            Route::get('/redisearch')->action([SearchController::class, 'redisearch'])->name('redisearch'),
            Route::get('/solr')->action([SearchController::class, 'solr'])->name('solr'),
            Route::get('/typesense')->action([SearchController::class, 'typesense'])->name('typesense'),
            Route::get('/multi')->action([SearchController::class, 'multi'])->name('multi'),
            Route::get('/read-write')->action([SearchController::class, 'readWrite'])->name('read-write'),
        ),
];
