<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class Media
{
    public $namespaces;

    public $globalDefault = false;

    public $default_media = 'media/default.png';

    public \Intervention\Image\ImageManager $imageDriver;

    public \Illuminate\Contracts\Filesystem\Filesystem $disk;

    public function __construct($storageDisc = null)
    {
        $this->namespaces = config('toneflix-fileable.collections');

        $this->globalDefault = true;

        $this->imageDriver = new ImageManager(new Driver());

        $this->disk = Storage::disk($storageDisc ?? Storage::getDefaultDriver());
    }

    /**
     * Fetch an file from the storage
     *
     * @deprecated 1.1.0 Use getMedia() instead.
     */
    public function image(string $type, string $src = null): ?string
    {
        return $this->getMedia($type, $src);
    }

    /**
     * Fetch an file from the storage
     *
     * @param  string  $type    The type of file to fetch (This will be the configured collection)
     * @param  string  $src     The file name
     * @param  bool  $returnPath  If true the method will return the relative path of the file
     * @param  bool  $legacyMode  If true the method will remove the file path from the $src
     */
    public function getMedia(string $type, string $src = null, $returnPath = false, $legacyMode = false): ?string
    {
        if ($legacyMode) {
            $src = str($src)->afterLast('/')->__toString();
        }

        if (str($src)->contains(':') && ! str($src)->contains('http')) {
            $type = str($src)->before(':')->__toString();
            $src = str($src)->after(':')->__toString();
        }

        $getPath = Arr::get($this->namespaces, $type.'.path');
        $default = Arr::get($this->namespaces, $type.'.default');

        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $port = parse_url($src, PHP_URL_PORT);
            $url = str($src)->replace('localhost:'.$port, 'localhost');

            if ($returnPath === true) {
                return parse_url($src, PHP_URL_PATH);
            }

            return Initiator::asset($url->replace('localhost', request()->getHttpHost()), true);
        }

        if (! $src || ! $this->disk->exists($prefix.$getPath.$src)) {
            if (filter_var($default, FILTER_VALIDATE_URL)) {
                if ($returnPath === true) {
                    return parse_url($default, PHP_URL_PATH);
                }

                return $default;
            } elseif (! $this->disk->exists($prefix.$getPath.$default)) {
                if ($returnPath === true) {
                    return $this->default_media;
                }

                return Initiator::asset($this->default_media);
            }

            if ($returnPath === true) {
                return Initiator::asset($getPath.$default, true);
            }

            return Initiator::asset($getPath.$default);
        }

        if ($returnPath === true) {
            return Initiator::asset($getPath.$src, true);
        } elseif (str($type)->contains('private.')) {
            $secure = Arr::get($this->namespaces, $type.'.secure', false) === true ? 'secure' : 'open';

            return Initiator::asset(route("fileable.{$secure}.file", [
                'file' => base64url_encode($getPath.$src),
            ]), true);
        }

        return Initiator::asset($getPath.$src);
    }

    /**
     * Check if the file exists
     */
    public function exists(string $type, string $src = null): bool
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        if (! $src || ! $this->disk->exists($prefix.$getPath.$src)) {
            return false;
        }

        return true;
    }

    /**
     * Get the relative path of the file
     */
    public function getPath(string $type, string $src = null): ?string
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $default = Arr::get($this->namespaces, $type.'.default');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            return parse_url($src, PHP_URL_PATH);
        }

        if (! $src || ! $this->disk->exists($prefix.$getPath.$src)) {
            if (filter_var($default, FILTER_VALIDATE_URL)) {
                return parse_url($default, PHP_URL_PATH);
            } elseif (! $this->disk->exists($prefix.$getPath.$default)) {
                return $this->default_media;
            }

            return $getPath.$default;
        }

        return $getPath.$src;
    }

    public function getDefaultMedia(string $type): string
    {
        $default = Arr::get($this->namespaces, $type.'.default');
        $path = Arr::get($this->namespaces, $type.'.path');

        if (filter_var($default, FILTER_VALIDATE_URL)) {
            return $default;
        }

        return Initiator::asset($path.$default);
    }

    /**
     * Render the private file
     *
     * @return void
     */
    public function privateFile(string $file)
    {
        $src = base64url_decode($file);

        if ($this->disk->exists($src)) {
            $mime = $this->disk->mimeType($src);

            // create response and add encoded image data
            if (str($mime)->contains('image')) {
                return response()->file($this->disk->path($src), [
                    'Cross-Origin-Resource-Policy' => 'cross-origin',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            } else {
                $response = Response::make($this->disk->get($src));

                // set headers
                return $response->header('Content-Type', $mime)
                    ->header('Cross-Origin-Resource-Policy', 'cross-origin')
                    ->header('Access-Control-Allow-Origin', '*');
            }
        }
    }

    /**
     * Fetch a file from the storage
     *
     * @param  string  $old
     */
    public function save(string $type, string $file_name = null, $old = null, $index = null): ?string
    {
        // Get the file path
        $getPath = Arr::get($this->namespaces, $type.'.path');

        // Get the file path prefix
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $request = request();
        $old_path = $prefix.$getPath.$old;
        if ($request->hasFile($file_name)) {
            if ($old && $this->disk->exists($old_path) && $old !== 'default.png') {
                $this->disk->delete($old_path);
            }

            // If an index is provided get the file from the array by index
            // This is useful when you have multiple files with the same name
            if ($index !== null) {
                $requestFile = $request->file($file_name)[$index];
            } else {
                $requestFile = $request->file($file_name);
            }

            // Give the file a new name and append extension
            $rename = rand().'_'.rand().'.'.$requestFile->extension();

            $this->disk->putFileAs(
                $prefix.$getPath, // Path
                $requestFile, // Request File
                $rename // Directory
            );

            // Reset the file instance
            $request->offsetUnset($file_name);

            // If the file is an image resize it
            $size = Arr::get($this->namespaces, $type.'.size');

            $mime = $this->disk->mimeType($prefix.$getPath.$rename);

            $size = Arr::get($this->namespaces, $type.'.size');

            // If the file is an image resize it if size is available
            if ($size && str($mime)->contains('image')) {
                $this->imageDriver->read($this->disk->path($prefix.$getPath.$rename))
                    ->cover(Arr::first($size), Arr::last($size))
                    ->save();
            }

            // Return the new file name
            return $rename;
        } elseif ($request->has($file_name)) {
            if ($old && $this->disk->exists($old_path) && $old !== 'default.png') {
                $this->disk->delete($old_path);
            }

            return null;
        }

        // If no file is provided return the old file name
        return $old;
    }

    /**
     * Save a base64 encoded image string to storage
     *
     * @param  string  $old
     */
    public function saveEncoded(string $type, string $encoded_string = null, $old = null, $index = null): ?string
    {
        if (! $encoded_string) {
            return null;
        }

        // Get the file path
        $getPath = Arr::get($this->namespaces, $type.'.path');

        // Get the file path prefix
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $old_path = $prefix.$getPath.$old;

        // Delete the old file
        if ($old && $this->disk->exists($old_path) && $old !== 'default.png') {
            $this->disk->delete($old_path);
        }

        // Check if the string has a base64 prefix and remove it
        if (str($encoded_string)->contains('base64,')) {
            $encoded_string = str($encoded_string)->after('base64,')->__toString();
        }

        // Decode the string
        $imgdata = base64_decode($encoded_string);

        // Get the file extension
        $ext = str(finfo_buffer(finfo_open(), $imgdata, FILEINFO_EXTENSION))->before('/')->toString();

        // Get the file extension
        $extension = collect([
            '' => 'png',
            '???' => str($encoded_string)->between('/', ';')->toString(),
            'svg+xml' => 'svg',
            'jpeg' => 'jpg',
            'plain' => 'txt',
            'octet-stream' => 'bin',
        ])->get($ext, $ext);

        // Give the file a new name and append extension
        $rename = rand().'_'.rand().'.'.$extension;
        $path = $prefix.trim($getPath, '/').'/'.$rename;

        // Store the file
        $this->disk->put($path, base64_decode($encoded_string));

        return $rename;
    }

    /**
     * Save or retrieve an image from cache
     *
     * @return array{cc:string,mm:string}|null
     */
    public function cached(string $fileName): ?array
    {
        $file = null;
        $content = null;

        Cache::delete("fileable.$fileName");

        // Check if the file exists in cache
        if (Cache::has("fileable.$fileName")) {
            $content = Cache::get("fileable.$fileName");
        } else {
            // Loop through all the filesystems.links to find the file
            foreach (collect(config('filesystems.links'))->values() as $path) {
                $file = collect(File::allFiles($path))
                    ->firstWhere(fn ($e) => $e->getFilename() === $fileName && str(File::mimeType($e))->contains('image'));

                if (! $file) {
                    continue;
                }
            }

            // Save the file to cache
            if ($file) {
                $content = ['cc' => $file->getContents(), 'mm' => File::mimeType($file)];
                Cache::put("fileable.$fileName", $content);
            }
        }

        return $content;
    }

    /**
     * Fetch the given image from the cache and resize it.
     */
    public function resizeResponse(string $fileName, string $size): \Illuminate\Http\Response
    {
        $cached = $this->cached($fileName);

        if ($cached) {
            // Get the image resolution
            $res = explode(
                'x',
                config(
                    "toneflix-fileable.image_sizes.$size",
                    config('toneflix-fileable.image_sizes.md', '694')
                )
            );

            // Resize the image
            $resized = $this->imageDriver->read($cached['cc'])
                ->cover(Arr::first($res), Arr::last($res))
                ->encode();

            // Make the Http Response
            $response = Response::make($resized);

            // set the headers
            return $response->header('Content-Type', $cached['mm'])
                ->header('Cross-Origin-Resource-Policy', 'cross-origin')
                ->header('Access-Control-Allow-Origin', '*');
        }

        return abort(404);
    }

    /**
     * Delete a file from the storage
     */
    public function delete(string $type, string $src = null): ?string
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $path = $prefix.$getPath.$src;

        if ($src && $this->disk->exists($path) && $src !== 'default.png') {
            $this->disk->delete($path);
        }

        return $path;
    }
}
