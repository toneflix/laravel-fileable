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
     * Fetch an image from the storage
     *
     * @param  string  $type
     * @param  string  $src
     * @return string
     */
    public function image(string $type, string $src = null): string|null
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $default = Arr::get($this->namespaces, $type.'.default');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $port = parse_url($src, PHP_URL_PORT);
            $url = str($src)->replace('localhost:'.$port, 'localhost');

            return $url->replace('localhost', request()->getHttpHost());
        }

        if (! $src || ! Storage::exists($prefix.$getPath.$src)) {
            if (filter_var($default, FILTER_VALIDATE_URL)) {
                return $default;
            } elseif (! Storage::exists($prefix.$getPath.$default)) {
                return asset($this->default_media);
            }

            return asset($getPath.$default);
        }

        if (str($type)->contains('private.')) {
            return route('fileable.secure.file', ['file' => base64url_encode($getPath.$src)]);
        }

        return asset($getPath.$src);
    }

    public function privateFile($file)
    {
        $src = base64url_decode($file);
        if (Storage::exists($src)) {
            $mime = Storage::mimeType($src);
            // create response and add encoded image data
            if (! str($mime)->contains('image')) {
                $img = $this->imageDriver->make(storage_path('app/'.$src));
                $response = Response::make($img->encode(str($mime)->explode('/')->last()));
            } else {
                $response = Response::make(Storage::get($src));
            }
            // set headers
            return $response->header('Content-Type', $mime)
                    ->header('Cross-Origin-Resource-Policy', 'cross-origin')
                    ->header('Access-Control-Allow-Origin', '*');
        }
    }

    /**
     * Fetch an image from the storage
     *
     * @param  string  $type
     * @param  string  $file_name
     * @param  string  $old
     * @return string
     */
    public function save(string $type, string $file_name = null, $old = null): string|null
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $prefix = ! str($type)->contains('private.') ? 'public/' : '/';

        $request = request();
        if ($request->hasFile($file_name)) {
            $old_path = $prefix.$getPath.$old;

            if ($old && Storage::exists($old_path) && $old !== 'default.png') {
                Storage::delete($old_path);
            }
            $rename = rand().'_'.rand().'.'.$request->file($file_name)->extension();

            $request->file($file_name)->storeAs(
                $prefix.trim($getPath, '/'), $rename
            );
            $request->offsetUnset($file_name);

            // Resize the image
            $size = Arr::get($this->namespaces, $type.'.size');
            if ($size) {
                $this->imageDriver->make(storage_path('app/'.$prefix.$getPath.$rename))
                    ->fit($size[0], $size[1])
                    ->save();
            }

            return  $rename;
        }

        return $old;
    }

    /**
     * Delete an image from the storage
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
