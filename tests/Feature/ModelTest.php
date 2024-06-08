<?php

use ToneflixCode\LaravelFileable\Tests\Models\User;

test('has-avatar', function () {
    expect(true)->toBeTrue();
    $user = User::factory()->create();

    $image = $user->files['avatar'] ?? $user->files['image'] ?? '';

    expect($image !== '')->toBeTrue();
    expect($image !== null)->toBeTrue();
    // expect(mb_stripos($image, 'default.') === false)->toBeTrue();
});
