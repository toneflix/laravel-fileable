<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

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
     * @param  string  $type
     * @param  string  $src
     * @return string
     * @deprecated 1.1.0 Use getMedia() instead.
     */
    public function image(string $type, string $src = null): string|null
    {
        return $this->getMedia($type, $src);
    }

    /**
     * Fetch an file from the storage
     *
     * @param  string  $type
     * @param  string  $src
     * @return string
     */
    public function getMedia(string $type, string $src = null, $returnPath = false): string|null
    {
        $getPath = Arr::get($this->namespaces, $type . '.path');

        $default = Arr::get($this->namespaces, $type . '.default');

        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $port = parse_url($src, PHP_URL_PORT);

            $url = str($src)->replace('localhost:' . $port, 'localhost');

            if ($returnPath === true) {
                return parse_url($src, PHP_URL_PATH);
            }

            return $url->replace('localhost', request()->getHttpHost());
        }

        if (! $src || ! $this->disk->exists($prefix . $getPath . $src)) {
            if (filter_var($default, FILTER_VALIDATE_URL)) {
                if ($returnPath === true) {
                    return parse_url($default, PHP_URL_PATH);
                }

                return $default;
            } elseif (! $this->disk->exists($prefix . $getPath . $default)) {
                if ($returnPath === true) {
                    return $this->default_media;
                }

                return asset($this->default_media);
            }

            if ($returnPath === true) {
                return $getPath . $default;
            }

            return asset($getPath . $default);
        }

        if (str($type)->contains('private.')) {
            $secure = Arr::get($this->namespaces, $type . '.secure', false) === true ? 'secure' : 'open';
            return route("fileable.{$secure}.file", ['file' => base64url_encode($getPath . $src)]);
        }


        if ($returnPath === true) {
            return $getPath . $src;
        }

        return asset($getPath . $src);
    }

    public function getDefaultMedia(string $type): string
    {
        $default = Arr::get($this->namespaces, $type . '.default');
        
        $path = Arr::get($this->namespaces, $type . '.path');

        if (filter_var($default, FILTER_VALIDATE_URL)) {
            return $default;
        }

        return asset($path . $default);
    }

    public function privateFile($file)
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
     * @param  string  $type
     * @param  string  $file_name
     * @param  string  $old
     * @return string
     */
    public function save(string $type, string $file_name = null, $old = null, $index = null): string|null
    {
        // Get the file path
        $getPath = Arr::get($this->namespaces, $type . '.path');

        // Get the file path prefix
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $request = request();
        if ($request->hasFile($file_name)) {
            $old_path = $prefix . $getPath . $old;

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
            $rename = rand() . '_' . rand() . '.' . $requestFile->extension();

            // Store the file
            $requestFile->storeAs(
                $prefix . trim($getPath, '/'),
                $rename
            );

            // Reset the file instance
            $request->offsetUnset($file_name);

            // If the file is an image resize it
            $mime = $this->disk->mimeType($prefix . $getPath . $rename);
            $size = Arr::get($this->namespaces, $type . '.size');
            if ($size && str($mime)->contains('image')) {
                $this->imageDriver->read(storage_path('app/' . $prefix . $getPath . $rename))
                    ->cover($size[0], $size[1])
                    ->save();
            }

            // Return the new file name
            return  $rename;
        }

        // If no file is provided return the old file name
        return $old;
    }

    /**
     * Save or retrieve an image from cache
     *
     * @param string $fileName
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

                if (!$file) {
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
     *
     * @param string $fileName
     * @param string $size
     * @return \Illuminate\Http\Response
     */
    public function resizeResponse(string $fileName, string $size): \Illuminate\Http\Response
    {
        $cached = $this->cached($fileName);

        if ($cached) {
            // Get the image resolution
            $res = explode(
                'x',
                config("toneflix-fileable.image_sizes.$size", config("toneflix-fileable.image_sizes.md", '694'))
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
     *
     * @param  string  $type
     * @param  string  $src
     * @return string
     */
    public function delete(string $type, string $src = null): string|null
    {
        $getPath = Arr::get($this->namespaces, $type . '.path');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $path = $prefix . $getPath . $src;

        if ($src && $this->disk->exists($path) && $src !== 'default.png') {
            $this->disk->delete($path);
        }

        return $path;
    }
}