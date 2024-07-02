<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use ToneflixCode\LaravelFileable\Media;
use ToneflixCode\LaravelFileable\Tests\Models\User;

test('can automatically upload file', function () {
    $user = User::factory()->create();

    Route::post('account', function (Request $request) {
        $u = $request->user();
        $u->name = fake('En-NG')->name;
        $u->save();

        return $u;
    });

    $response = $this->actingAs($user)
        ->post('account', [
            'image' => UploadedFile::fake()->image('avatar.jpg')
        ]);

    expect($response->original->get_files['image']['size'])->toBeGreaterThan(0);
});

test('can save file', function () {
    $user = User::factory()->create();
    Storage::fake('default');

    Route::post('account', function (Request $request) {
        $u = $request->user();

        return (new Media('default'))->save('avatar', 'image', $u->image);
    });

    $response = $this->actingAs($user)
        ->post('account', [
            'image' => UploadedFile::fake()->image('avatar.jpg')
        ]);

    $file = Storage::disk('default')->path('public/' . (new Media('default'))->getPath('avatar', $response->original));

    expect(file_exists($file))->toBeTrue();
});

test('can delete file', function () {
    $user = User::factory()->create();
    Storage::fake('default');

    Route::post('account', function (Request $request) {
        $u = $request->user();
        $image = (new Media('default'))->save('avatar', 'image', $u->image);
        return $image;
    });

    $response = $this->actingAs($user)
        ->post('account', [
            'image' => UploadedFile::fake()->image('avatar.jpg')
        ]);

    (new Media('default'))->delete('avatar', $response->original);
    $file = Storage::disk('default')->path('public/' . (new Media('default'))->getPath('avatar', $response->original));

    expect(file_exists($file))->toBeFalse();
});