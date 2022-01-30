<?php

use Illuminate\Routing\UrlGenerator;
use Innocenzi\Vite\Exceptions\NoSuchConfigurationException;

it('uses the default configuration when not specifying one', function () {
    expect(vite()->getClientScriptTag())
        ->toEqual('<script type="module" src="http://localhost:3000/@vite/client"></script>');
});

it('throws when accessing a configuration that does not exist', function () {
    vite('unknown-configuration')->getClientScriptTag();
})->throws(NoSuchConfigurationException::class);

it('uses the right configuration when specifying it', function () {
    set_vite_config('custom', [
        'dev_server' => [
            'url' => 'http://localhost:3001',
        ],
    ]);

    expect(vite('custom')->getConfig('dev_server.url'))->toBe('http://localhost:3001');
});

it('generates URLs relative to the app URL by default in production', function () {
    set_base_path_in('builds');
    set_env('production');
    
    expect(using_manifest('builds/public/with-css/manifest.json')->getTags()->toHtml())
        ->toContain('<link rel="stylesheet" href="http://localhost/with-css/assets/test.65bd481b.css" />')
        ->toContain('<script type="module" src="http://localhost/with-css/assets/test.a2c636dd.js"></script>');
});

it('generates URLs relative to the configured ASSET_URL in production', function () {
    set_base_path_in('builds');
    set_env('production');

    $property = new ReflectionProperty(UrlGenerator::class, 'assetRoot');
    $property->setAccessible(true);
    $property->setValue(app('url'), 'https://s3.us-west-2.amazonaws.com/12345678');
    
    expect(using_manifest('builds/public/with-css/manifest.json')->getTags()->toHtml())
        ->toContain('<link rel="stylesheet" href="https://s3.us-west-2.amazonaws.com/12345678/with-css/assets/test.65bd481b.css" />')
        ->toContain('<script type="module" src="https://s3.us-west-2.amazonaws.com/12345678/with-css/assets/test.a2c636dd.js"></script>');
});