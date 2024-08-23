<?php

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use ToneflixCode\LaravelFileable\Controllers\FileController;
use ToneflixCode\LaravelFileable\Tests\Models\User;

test('can view dynamic', function () {
    $user = User::factory()->create();

    Route::post('account', function (Request $request) {
        $u = $request->user();
        $u->save();

        return $u;
    });

    Route::get('image/{file}', [FileController::class, 'show']);

    $this->actingAs($user)->post('account', ['image' => UploadedFile::fake()->image('avatar.jpg')]);

    $encodedFilename = str($user->getFiles['image']['dynamicLink'])->afterLast('/')->toString();
    $response = $this->actingAs($user)->get('image/'.$encodedFilename);

    expect($response->baseResponse)->toBeInstanceOf(\Symfony\Component\HttpFoundation\BinaryFileResponse::class);
});

test('can stream dynamic audio/video', function () {
    $user = User::factory()->create();

    Route::post('account', function (Request $request) {
        $u = $request->user();
        $u->save();

        return $u;
    });

    Route::get('video/{file}', [FileController::class, 'show']);

    $video = new UploadedFile(realpath(__DIR__.'/../flowbite.mp4'), 'video.mp4', 'video/mp4', null, true);

    $this->actingAs($user)->post('account', ['video' => $video]);

    $encodedFilename = str($user->getFiles['video']['dynamicLink'])->afterLast('/')->toString();
    $response = $this->actingAs($user)->get('video/'.$encodedFilename);

    expect($response->baseResponse)->toBeInstanceOf(\Symfony\Component\HttpFoundation\StreamedResponse::class);
});
