<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', static fn (): string => (new \App\Http\Controllers\SearchController())->home());
Route::get('/algolia', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->algolia());
Route::get('/elasticsearch', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->elasticsearch());
Route::get('/loupe', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->loupe());
Route::get('/meilisearch', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->meilisearch());
Route::get('/memory', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->memory());
Route::get('/opensearch', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->opensearch());
Route::get('/redisearch', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->redisearch());
Route::get('/solr', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->solr());
Route::get('/typesense', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->typesense());
Route::get('/multi', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->multi());
Route::get('/read-write', static fn (): \Symfony\Component\HttpFoundation\Response => (new \App\Http\Controllers\SearchController())->readWrite());
