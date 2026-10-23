<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Toneflix\LaravelFileable\Initiator;
use Toneflix\LaravelFileable\Media;

/**
 * A disk that is not on this server, like S3. Files live in memory and
 * plain URLs point at a fake bucket.
 */
beforeEach(function () {
    Storage::extend('memory', function ($app, $config) {
        $adapter = new InMemoryFilesystemAdapter;

        return new FilesystemAdapter(new Filesystem($adapter, $config), $adapter, $config);
    });

    config()->set('filesystems.disks.remote', [
        'driver' => 'memory',
        'url' => 'https://bucket.test',
    ]);
    config()->set('filesystems.default', 'remote');

    Storage::forgetDisk('remote');
});

test('images are resized and stored on a remote disk', function () {
    $name = (new Media)->save('avatar', UploadedFile::fake()->image('avatar.jpg', 1000, 800));

    $path = 'avatars/'.$name;
    expect(Storage::disk('remote')->exists($path))->toBeTrue();

    $size = getimagesizefromstring(Storage::disk('remote')->get($path));
    expect([$size[0], $size[1]])->toBe([400, 400]);
});

test('remote files get disk URLs and plain paths', function () {
    $media = new Media;
    $name = $media->save('avatar', UploadedFile::fake()->image('avatar.jpg'));

    expect($media->getMedia('avatar', $name))->toBe('https://bucket.test/avatars/'.$name)
        ->and($media->getMedia('avatar', $name, true))->toBe('avatars/'.$name)
        ->and($media->mediaInfo('avatar', $name)['size'])->toBeGreaterThan(0);
});

test('remote files are signed when temporary urls are on', function () {
    Storage::disk('remote')->buildTemporaryUrlsUsing(
        fn ($path, $expiration) => 'https://bucket.test/'.$path.'?signed='.$expiration->getTimestamp()
    );
    config()->set('toneflix-fileable.temporary_urls', true);

    $media = new Media;
    $name = $media->save('avatar', UploadedFile::fake()->image('avatar.jpg'));

    expect($media->getMedia('avatar', $name))->toStartWith('https://bucket.test/avatars/'.$name.'?signed=');
});

test('dynamic links to remote files redirect to a signed url or stream the file', function () {
    $media = new Media;
    $name = $media->save('avatar', UploadedFile::fake()->image('avatar.jpg'));
    $file = Initiator::base64urlEncode('/avatars/'.$name);

    // The memory disk can't sign URLs, so the file is streamed.
    $streamed = $media->dynamicFile($file);
    expect($streamed->getStatusCode())->toBe(200);

    Storage::disk('remote')->buildTemporaryUrlsUsing(fn ($path) => 'https://bucket.test/'.$path.'?signed=1');

    $redirect = (new Media)->dynamicFile($file);
    expect($redirect->getStatusCode())->toBe(302)
        ->and($redirect->headers->get('Location'))->toBe('https://bucket.test/avatars/'.$name.'?signed=1');
});

test('non image files are stored as they are on a remote disk', function () {
    $video = new UploadedFile(realpath(__DIR__.'/../flowbite.mp4'), 'video.mp4', 'video/mp4', null, true);

    $name = (new Media)->save('default', $video);

    expect(Storage::disk('remote')->size('media/default/'.$name))->toBe(filesize(__DIR__.'/../flowbite.mp4'));
});
