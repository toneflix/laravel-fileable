<?php

namespace ToneflixCode\LaravelFileable\Facades;

use Illuminate\Support\Facades\Facade;
use ToneflixCode\LaravelFileable\Media;

/**
 * @see \ToneflixCode\LaravelFileable\Media
 *
 * @method ?string getMedia(string $type, string $src = null, $returnPath = false, $legacyMode = false) Fetch an file from the storage
 * @method bool exists(string $type, string $src = null) Check if the file exists
 * @method ?string getPath(string $type, string $src = null) Get the relative path of the file
 * @method string getDefaultMedia(string $type)
 * @method string dynamicFile() Render the private file
 * @method ?string save(string $type, ?string $file_name = null, ?string $old = null, ?string $index = null) Save a file to the storage
 * @method ?string saveEncoded(string $type, ?string $encoded_string = null, ?string $old = null, ?string $index = null)
 * @method array{cc:string,mm:string}|null cached(string $fileName) Save or retrieve a file from cache
 * @method \Illuminate\Http\Response resizeResponse(string $fileName, string $size) Fetch the given image from the cache and resize it.
 * @method ?string delete(string $type, string $src = null) Delete a file from the storage
 */
class Fileable extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Media::class;
    }
}
