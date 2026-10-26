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

        // The memory adapter has no URLs of its own, so build them from the disk's "url" like S3 does.
        return new class(new Filesystem($adapter, $config), $adapter, $config) extends FilesystemAdapter
        {
            /** Lookups made against the disk, like the requests S3 would get. */
            public array $lookups = [];

            public function url($path)
            {
                return rtrim($this->config['url'], '/').'/'.ltrim($path, '/');
            }

            public function exists($path)
            {
                $this->lookups[] = "exists:$path";

                return parent::exists($path);
            }

            public function fileExists($path)
            {
                $this->lookups[] = "fileExists:$path";

                return parent::fileExists($path);
            }

            public function mimeType($path)
            {
                $this->lookups[] = "mimeType:$path";

                return parent::mimeType($path);
            }

            public function size($path)
            {
                $this->lookups[] = "size:$path";

                return parent::size($path);
            }
        };
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
        ->and($media->getMedia('avatar', $name, true))->toBe('avatars/'.$name);
});

test('remote file links are built without asking the disk', function () {
    $media = new Media;

    expect($media->getMedia('avatar', 'missing.jpg'))->toBe('https://bucket.test/avatars/missing.jpg')
        ->and($media->getMedia('avatar', 'missing.jpg', true))->toBe('avatars/missing.jpg');

    $info = $media->mediaInfo('avatar', 'missing.jpg');

    expect($info['url'])->toBe('https://bucket.test/avatars/missing.jpg')
        ->and($info['dynamicLink'])->toBe('https://bucket.test/avatars/missing.jpg')
        ->and($info['mime'])->toBe('image/jpeg')
        ->and($info['isImage'])->toBeTrue()
        ->and($info['size'])->toBe(0);

    expect(Storage::disk('remote')->lookups)->toBe([]);
});

test('private remote files link straight to a signed url', function () {
    Storage::disk('remote')->buildTemporaryUrlsUsing(
        fn ($path) => 'https://bucket.test/'.$path.'?signed=1'
    );

    $media = new Media;

    expect($media->getMedia('private.images', 'photo.jpg'))->toBe('https://bucket.test/files/images/photo.jpg?signed=1')
        ->and($media->mediaInfo('avatar', 'photo.jpg')['secureLink'])->toBe('https://bucket.test/avatars/photo.jpg?signed=1');
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
