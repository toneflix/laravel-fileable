<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use League\Flysystem\Local\LocalFilesystemAdapter;
use ToneflixCode\LaravelFileable\Facades\Media as MediaFacade;

class Media
{
    public array $namespaces;

    /**
     * Determines of the save method should also return the
     * original file name as recieved from the request
     */
    public bool $getOriginalName = false;

    /**
     * Global Default
     */
    public bool $globalDefault = false;

    /**
     * Default file to use if file is missing
     */
    public string $default_media = 'media/default.png';

    /**
     * Partern used for generating file names for saving.
     */
    public ?string $fileNamePattern = '000000000-000000000';

    /**
     * The image driver for image manipulation
     */
    public \Intervention\Image\ImageManager $imageDriver;

    /**
     * The prefered disk to use
     */
    public \Illuminate\Contracts\Filesystem\Filesystem $disk;

    public function __construct(
        ?string $disk = null,
        bool $getOriginalName = false,
    ) {
        $this->fileNamePattern = config('toneflix-fileable.file_name_pattern', '000000000-000000000');
        $this->getOriginalName = $getOriginalName;
        $this->globalDefault = true;
        $this->imageDriver = new ImageManager(new Driver);
        $this->namespaces = config('toneflix-fileable.collections');
        $this->disk = Storage::disk($disk ?? Storage::getDefaultDriver());
    }

    /**
     * Fetch an file from the storage
     *
     * @deprecated 1.1.0 Use getMedia() instead.
     */
    public function image(string $type, ?string $src = null): ?string
    {
        return $this->getMedia($type, $src);
    }

    /**
     * Fetch an file from the storage
     *
     * @param  string  $type  The type of file to fetch (This will be the configured collection)
     * @param  string  $src  The file name
     * @param  bool  $returnPath  If true the method will return the relative path of the file
     * @param  bool  $legacyMode  If true the method will remove the file path from the $src
     */
    public function getMedia(string $type, ?string $src = null, $returnPath = false, $legacyMode = false): ?string
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

        $prefix = $this->getPrefix($type);

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $port = parse_url($src, PHP_URL_PORT);
            $url = str($src)->replace('localhost:'.$port, 'localhost');

            if ($returnPath === true) {
                return parse_url($src, PHP_URL_PATH);
            }

            return Initiator::asset($url->replace('localhost', request(null)->getHttpHost()), true, $this->disk);
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

                return Initiator::asset($this->default_media, false, $this->disk);
            }

            if ($returnPath === true) {
                return Initiator::asset($getPath.$default, true, $this->disk);
            }

            return Initiator::asset($getPath.$default, false, $this->disk);
        }

        if ($returnPath === true) {
            return Initiator::asset($getPath.$src, true, $this->disk);
        } elseif (str($type)->contains('private.')) {
            $secure = Arr::get($this->namespaces, $type.'.secure', false) === true ? 'secure' : 'open';

            return Initiator::asset(route("fileable.{$secure}.file", [
                'file' => Initiator::base64urlEncode($getPath.$src),
            ]), true, $this->disk);
        }

        return Initiator::asset($getPath.$src, false, $this->disk);
    }

    /**
     * Check if the file exists
     */
    public function exists(string $type, ?string $src = null): bool
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $prefix = $this->getPrefix($type);

        if (! $src || ! $this->disk->exists($prefix.$getPath.$src)) {
            return false;
        }

        return true;
    }

    /**
     * Get the relative path of the file
     */
    public function getPath(string $type, ?string $src = null): ?string
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $default = Arr::get($this->namespaces, $type.'.default');
        $prefix = $this->getPrefix($type);

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

        return Initiator::asset($path.$default, false, $this->disk);
    }

    /**
     * Render the private file
     *
     * @return void
     */
    public function dynamicFile(string $file)
    {
        $src = Initiator::base64urlDecode($file);

        $headers = [
            'Cross-Origin-Resource-Policy' => 'cross-origin',
            'Access-Control-Allow-Origin' => '*',
        ];

        if ($this->disk->fileExists($src)) {
            if (in_array($this->disk->mimeType($src), config('toneflix-fileable.streamable_mimes', []))) {
                $stream = MediaFacade::streamer(
                    $this->disk->path($src),
                    $this->disk->mimeType($src),
                    $headers
                );

                return response()->stream(function () use ($stream) {
                    $stream->start();
                });
            }

            // create response and add encoded file data
            return response()->file($this->disk->path($src), $headers);
        }

        abort(404, 'File not found.');
    }

    /**
     * Save a file to the storage
     *
     * @param  string  $type  The name of the collection where this file should be saved
     * @param  string|UploadedFile|null  $file_name  The request filename property
     * @param  ?string  $old  The name of the old existing file to be deleted
     * @param  string|int|null  $index  Current index of the file if saving in a loop
     * @return null|string|array{new_name:string,original_name:string}
     */
    public function save(
        string $type,
        string|UploadedFile|null $file_name = null,
        ?string $old = null,
        string|int|null $index = null
    ): string|array|null {
        // Get the file path
        $getPath = Arr::get($this->namespaces, $type.'.path');

        // Get the file path prefix
        $prefix = $this->getPrefix($type);

        $request = request(null);
        $old_path = $prefix.$getPath.$old;

        // Adds support for saving files from an array index using wildcard request access
        $fn = str($file_name);
        $fileKey = $fn->replace('.*.', ".$index.")->replace('.*', ".$index")->toString();
        $fileList = $fn->contains('*') ? Arr::dot($request->allFiles()) : null;

        if ($file_name instanceof UploadedFile || $request->hasFile($file_name) || isset($fileList[$fileKey])) {

            if ($old && $this->disk->fileExists($old_path) && $old !== 'default.png') {
                $this->disk->delete($old_path);
            }

            if ($file_name instanceof UploadedFile) {
                $requestFile = $file_name;
            } elseif (isset($fileList[$fileKey])) {
                // If an index is provided get the file from the array by index
                // This is useful when you have multiple files with the same name
                $requestFile = $fileList[$fileKey];
            } elseif (! is_null($index) && isset($request->file($file_name)[$index])) {
                $requestFile = $request->file($file_name)[$index];
            } else {
                $requestFile = $request->file($file_name);
            }

            /** @var \Illuminate\Http\UploadedFile|\Illuminate\Http\UploadedFile[]|array|null $requestFile */
            $original_name = $requestFile->getClientOriginalName();

            // Give the file a new name and append extension
            $new_name = Initiator::generateStringFromPattern(
                $this->fileNamePattern ?? '000000000-000000000'
            ).'.'.$requestFile->extension();

            $this->disk->putFileAs(
                $prefix.$getPath, // Path
                $requestFile, // Request File
                $new_name // Directory
            );

            // Reset the file instance
            if (is_string($file_name)) {
                $request->offsetUnset($file_name);
            }

            // If the file is an image resize it
            $size = Arr::get($this->namespaces, $type.'.size');

            $mime = $requestFile->getMimeType();

            $size = Arr::get($this->namespaces, $type.'.size');

            // If the file is an image resize it if size is available
            if ($size && str($mime)->contains('image')) {
                $this->imageDriver->read($this->disk->path($prefix.$getPath.$new_name))
                    ->cover(Arr::first($size), Arr::last($size))
                    ->save();
            }

            if ($this->getOriginalName) {
                return [
                    'new_name' => $new_name,
                    'original_name' => $original_name,
                ];
            }

            // Return the new file name
            return $new_name;
        } elseif ($request->has($file_name)) {
            if ($old && $this->disk->fileExists($old_path) && $old !== 'default.png') {
                $this->disk->delete($old_path);
            }

            return null;
        }

        // If no file is provided return the old file name
        return $old;
    }

    /**
     * Save a base64 encoded image string to storage
     */
    public function saveEncoded(
        string $type,
        ?string $encoded_string = null,
        ?string $old = null,
        ?string $index = null
    ): ?string {
        if (! $encoded_string) {
            return null;
        }

        // Get the file path
        $getPath = Arr::get($this->namespaces, $type.'.path');

        // Get the file path prefix
        $prefix = $this->getPrefix($type);

        $old_path = $prefix.$getPath.$old;

        // Delete the old file
        if ($old && $this->disk->fileExists($old_path) && $old !== 'default.png') {
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
        $rename = Initiator::generateStringFromPattern(
            $this->fileNamePattern ?? '000000000-000000000'
        ).'.'.$extension;
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
     *  Returns the attributes of a bound media file.
     *
     * @param  ?string  $file_name
     */
    public function mediaInfo(string $type, ?string $src = null)
    {
        if (! $src) {
            return [
                'isImage' => '',
                'path' => '',
                'url' => '',
                'type' => '',
                'mime' => '',
                'size' => 0,
                'dynamicLink' => '',
                'secureLink' => '',
            ];
        }

        if (filter_var($src, FILTER_VALIDATE_URL)) {
            $file_path = $file_url = $dynamicLink = $secureLink = $src;
            $mime = str(\GuzzleHttp\Psr7\MimeType::fromFilename($src));
        } else {
            $prefix = $this->getPrefix($type);
            $file_path = $prefix.$this->getMedia($type, $src, true);

            $mime = str($this->disk->fileExists($file_path) ? $this->disk->mimeType($file_path) : 'unknown/unknown');

            $file_url = $this->getMedia($type, $src) ?: (new Media)->getDefaultMedia($type);

            $dynamicLink = route('fileable.open.file', Initiator::base64urlEncode($file_path));
            $secureLink = route('fileable.secure.file', Initiator::base64urlEncode($file_path));
        }

        $isImage = $mime->contains('image');

        $mediaType = $mime->beforeLast('/')->exactly('application')
            ? $mime->afterLast('/')
            : $mime->beforeLast('/')->toString();

        return [
            'url' => $file_url,
            'ext' => str($src)->afterLast('.')->toString(),
            'type' => $mediaType,
            'mime' => $mime->toString(),
            'size' => $mime->isNotEmpty() && $this->disk->fileExists($file_path) ? $this->disk->size($file_path) : 0,
            'path' => $file_path,
            'isImage' => $isImage,
            'dynamicLink' => $dynamicLink,
            'secureLink' => $secureLink,
        ];
    }

    /**
     * Delete a file from the storage
     *
     * @param  ?string  $file_name
     */
    public function delete(string $type, ?string $src = null): ?string
    {
        $getPath = Arr::get($this->namespaces, $type.'.path');
        $prefix = $this->getPrefix($type);

        $path = $prefix.$getPath.$src;

        if ($src && $this->disk->exists($path) && $src !== 'default.png') {
            $this->disk->delete($path);
        }

        return $path;
    }

    /**
     * Get the path prefix for the selected colection
     */
    public function getPrefix(string $colection): string
    {
        return match (true) {
            str($colection)->contains('private.') || ! $this->disk->getAdapter() instanceof LocalFilesystemAdapter => '/',
            default => 'public/',
        };
    }
}
