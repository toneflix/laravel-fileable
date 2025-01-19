<?php

// use Illuminate\Http\Request;
// use Illuminate\Http\UploadedFile;
// use Illuminate\Support\Facades\Event;
// use ToneflixCode\LaravelFileable\Events\FileSaved;
// use ToneflixCode\LaravelFileable\Tests\Models\User;

// test('FileSaved emits on save', function () {
//     Route::post('url', function (Request $request) {
//         // return Event::fakeFor(function () use ($request) {
//         $u = User::factory()->create();
//         $u->name = fake('En-NG')->name;
//         $u->save();

//         Event::listen(function (\ToneflixCode\LaravelFileable\Events\FileSaved $event) {
//             dd($event->model, $event->fileInfo);
//         });

//         // Event::assertDispatched(FileSaved::class);
//         // return $u;
//         // });
//     });

//     $response = $this->post('url', ['image' => UploadedFile::fake()->image('avatar.jpg')]);
// });