<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class Media
{
    public $globalDefault = false;

    public $default_media = 'media/default.png';

    public $namespaces;

    public function __construct()
    {
        $this->namespaces = config('toneflix-fileable.collections');
        $this->globalDefault = true;
        $this->imageDriver = new ImageManager(['driver' => 'gd']);
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
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $default = Arr::get($this->namespaces, $type.'.default');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $port = parse_url($src, PHP_URL_PORT);
            $url = str($src)->replace('localhost:'.$port, 'localhost');

            if ($returnPath === true) {
                return parse_url($src, PHP_URL_PATH);
            }
            return $url->replace('localhost', request()->getHttpHost());
        }

        if (! $src || ! Storage::exists($prefix.$getPath.$src)) {
            if (filter_var($default, FILTER_VALIDATE_URL)) {

                if ($returnPath === true) {
                    return parse_url($default, PHP_URL_PATH);
                }

                return $default;
            } elseif (! Storage::exists($prefix . $getPath . $default)) {

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
            $secure = Arr::get($this->namespaces, $type.'.secure', false) === true ? 'secure' : 'open';
            return route("fileable.{$secure}.file", ['file' => base64url_encode($getPath.$src)]);
        }


        if ($returnPath === true) {
            return $getPath . $src;
        }

        return asset($getPath . $src);
<<<<<<< HEAD
    }

    public function getDefaultMedia(string $type): string
    {
        $default = Arr::get($this->namespaces, $type.'.default');
        $path = Arr::get($this->namespaces, $type.'.path');

        if (filter_var($default, FILTER_VALIDATE_URL)) {
            return $default;
        }

        return asset($path . $default);
=======
>>>>>>> ba03cd13d243fbead53a98ae2dbfc2605cf6dc18
    }

    public function privateFile($file)
    {
        $src = base64url_decode($file);
        if (Storage::exists($src)) {

            $mime = Storage::mimeType($src);
            // create response and add encoded image data
            if (str($mime)->contains('image')) {
                return response()->file(Storage::path($src), [
                    'Cross-Origin-Resource-Policy' => 'cross-origin',
                    'Access-Control-Allow-Origin' => '*',
                ]);
            } else {
                $response = Response::make(Storage::get($src));
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
        $getPath = Arr::get($this->namespaces, $type.'.path');

        // Get the file path prefix
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $request = request();
        if ($request->hasFile($file_name)) {
            $old_path = $prefix.$getPath.$old;

            if ($old && Storage::exists($old_path) && $old !== 'default.png') {
                Storage::delete($old_path);
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

            // Store the file
            $requestFile->storeAs(
                $prefix.trim($getPath, '/'), $rename
            );

            // Reset the file instance
            $request->offsetUnset($file_name);

<<<<<<< HEAD
            // If the file is an image resize it
=======
            // Resize the image
>>>>>>> ba03cd13d243fbead53a98ae2dbfc2605cf6dc18
            $mime = Storage::mimeType($prefix . $getPath . $rename);
            $size = Arr::get($this->namespaces, $type.'.size');
            if ($size && str($mime)->contains('image')) {
                $this->imageDriver->make(storage_path('app/' . $prefix . $getPath . $rename))
                    ->fit($size[0], $size[1])
                    ->save();
            }

            // Return the new file name
            return  $rename;
        }

        // If no file is provided return the old file name
        return $old;
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
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $path = $prefix.$getPath.$src;

        if ($src && Storage::exists($path) && $src !== 'default.png') {
            Storage::delete($path);
        }

        return $path;
    }
}
