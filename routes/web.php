<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/docs'));

Route::get('/docs', function () {
    return response(file_get_contents(public_path('docs/index.html')), 200, [
        'Content-Type' => 'text/html',
    ]);
});

Route::get('/api/docs', function () {
    return response(file_get_contents(public_path('docs/index.html')), 200, [
        'Content-Type' => 'text/html',
    ]);
});

Route::get('/openapi.json', function () {
    return response()->json(json_decode(file_get_contents(public_path('openapi.json')), true));
});

Route::get('/openapi.yaml', function () {
    return response(file_get_contents(public_path('openapi.yaml')), 200, [
        'Content-Type' => 'text/yaml',
    ]);
});

Route::get('/courier-management-api.postman_collection.json', function () {
    return response()->json(json_decode(file_get_contents(public_path('courier-management-api.postman_collection.json')), true));
});
