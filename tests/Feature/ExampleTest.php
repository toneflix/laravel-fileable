<?php

use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\{get};

test('example', function () {
    expect(true)->toBeTrue();
});

it('has a welcome page', function () {
    Artisan::call('route:list');
    dd(Artisan::output());
    get('/')->assertStatus(200);
});