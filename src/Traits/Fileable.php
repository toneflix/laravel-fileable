<?php

namespace ToneflixCode\LaravelFileable\Traits;

use ToneflixCode\LaravelFileable\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

/**
 * A collection of usefull model manipulation classes.
 */
trait Fileable
{
    public $namespaces;

    public array $sizes;

    public string $collection = 'image';

    public string|array $file_name = 'file';

    protected bool $applyDefault = false;

    public static string $static_collection = 'image';

    public static string|array $static_file_name = 'file';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->sizes = config('toneflix-fileable.image_sizes');
        $this->namespaces = config('toneflix-fileable.collections');
        $this->registerFileable();
    }

    public static function boot()
    {
        parent::boot();

        static::registerEvents();

        if (is_array(self::$static_file_name)) {
            $images = [];
            foreach (self::$static_file_name as $file => $collection) {
                static::saved(function ($item) use ($file, $collection) {
                    $item->saveImage($file, $collection);
                });

                static::deleting(function ($item) use ($file, $collection) {
                    $item->removeFile($file, $collection);
                });
            }

            return $images;
        } else {
            static::saved(function ($item) {
                $item->saveImage(self::$static_file_name, self::$static_collection);
            });

            static::deleting(function ($item) {
                $item->removeFile(self::$static_file_name, self::$static_collection);
            });
        }
    }

    /**
     * Register a creating model event with the dispatcher.
     *
     * @param  \Illuminate\Events\QueuedClosure|\Closure|string|array  $callback
     * @return void
     */
    public static function uploadComplete($model)
    {
    }

    /**
     * All extra model events should be registered here instead of on the model
     * Overite this method on the model
     *
     * @return void
     */
    public static function registerEvents()
    {
    }

    /**
     *  Returns a list of all bound files.
     *
     * @deprecated  1.0.0    Use files instead, will be removed from future versions
    */
    public function images(): Attribute
    {
        return new Attribute(
            get: fn() => $this->files,
        );
    }

    /**
     *  Returns a list of all bound files.
    */
    public function files(): Attribute
    {
        return new Attribute(
            get: function () {
                if (is_array($this->file_name)) {
                    $files = [];
                    foreach ($this->file_name as $file => $collection) {
                        $files[$file] = $this->retrieveFile($file, $collection);
                    }

                    return $files;
                } else {
                    return [$this->file_name => $this->retrieveFile($this->file_name, $this->collection)];
                }
            },
        );
    }

    /**
     *  Returns a single media file from list of all bound files.
     */
    public function mediaFile(): Attribute
    {
        return new Attribute(
            get: function () {
                $file = null;
            // If the file name is an array, get the first file
                if (is_array($this->file_name)) {
                    foreach ($this->file_name as $file => $collection) {
                        $file_name = $file;
                        $collection = $collection;
                        break;
                    }
                } else {
                    $file_name = $this->file_name;
                    $collection = $this->collection;
                }

                return $this->retrieveFile($file_name, $collection) ?? (new Media())->getDefaultMedia($collection);
            },
        );
    }

    /**
     *  Returns the attribute of a single bound media file.
     */
    public function mediaFileInfo(): Attribute
    {
        return new Attribute(
            get: function () {
                $file = null;
            // If the file name is an array, get the first file
                if (is_array($this->file_name)) {
                    foreach ($this->file_name as $file => $collection) {
                        $file_name = $file;
                        $collection = $collection;
                        break;
                    }
                } else {
                    $file_name = $this->file_name;
                    $collection = $this->collection;
                }

                $prefix = !str($collection)->contains('private.') ? 'public/' : '/';
                $file_path = $prefix . $this->retrieveFile($file_name, $collection, true);

                $mime = Storage::exists($file_path) ? Storage::mimeType($file_path) : null;
                $isImage = str($mime)->contains('image');

                $file_url = $this->retrieveFile($file_name, $collection) ?? (new Media())->getDefaultMedia($collection);

                return [$file_name => [
                'isImage' => $isImage,
                'path' => $file_path,
                'url' => $file_url,
                'mime' => $mime,
                'size' => $mime && Storage::exists($file_path) ? Storage::size($file_path) : 0,
                ]];
            },
        );
    }

    /**
     *  Returns the default image for the model as an attribute
     */
    public function defaultImage(): Attribute
    {
        return Attribute::make(
            get: fn () => ($this->collection
                ? (new Media())->getDefaultMedia($this->collection)
                : asset('media/default.jpg')
            )
        );
    }

    public function responsiveImages(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (is_array($this->file_name)) {
                    $images = [];
                    foreach ($this->file_name as $file => $collection) {
                        $images[$file] = collect($this->sizes)->mapWithKeys(function ($size, $key) use ($file, $collection) {
                            $prefix = ! str($collection)->contains('private.') ? 'public/' : '/';

                            $isImage = str(Storage::mimeType($prefix . $this->retrieveFile($file, $collection, true)))
                                    ->contains('image');

                            if (!$isImage) {
                                return [$key => $this->default_image];
                            }

                            $asset = pathinfo($this->retrieveFile($file, $collection), PATHINFO_BASENAME);

                            return [$key => route('imagecache', [$size, $asset])];
                        });
                    }

                    return $images;
                } else {
                    return collect($this->sizes)->mapWithKeys(function ($size, $key) {
                        $prefix = ! str($this->collection)->contains('private.') ? 'public/' : '/';
                        $isImage = str(Storage::mimeType($prefix . $this->retrieveFile($this->file_name, $this->collection, true)))
                                ->contains('image');

                        if (!$isImage) {
                            return [$key => $this->default_image];
                        }

                        $asset = pathinfo($this->retrieveFile($this->file_name, $this->collection), PATHINFO_BASENAME);

                        return [$key = route('imagecache', [$size, $asset])];
                    });
                }
            },
        );
    }

    /**
     *  Returns a list of bound files with a little more detal.
    */
    public function getFiles(): Attribute
    {
        return new Attribute(
            get: function () {
                if (is_array($this->file_name)) {
                    $files = [];
                    foreach ($this->file_name as $file => $collection) {
                        $prefix = ! str($collection)->contains('private.') ? 'public/' : '/';
                        $file_path = $prefix . $this->retrieveFile($file, $collection, true);

                        $mime = Storage::exists($file_path) ? Storage::mimeType($file_path) : null;
                        $isImage = str($mime)->contains('image');
                        $file_url = $this->retrieveFile($file, $collection);

                        $files[$file] = [
                        'isImage' => $isImage,
                        'path' => $file_path,
                        'url' => $file_url,
                        'mime' => $mime,
                        'size' => $mime && Storage::exists($file_path) ? Storage::size($file_path) : 0,
                        ];
                    }

                    return $files;
                } else {
                    $prefix = ! str($this->collection)->contains('private.') ? 'public/' : '/';
                    $file_path = $prefix . $this->retrieveFile($this->file_name, $this->collection, true);

                    $mime = Storage::exists($file_path) ? Storage::mimeType($file_path) : null;
                    $isImage = str($mime)->contains('image');

                    $file_url = $this->retrieveFile($this->file_name, $this->collection);
                    return [$this->file_name => [
                    'isImage' => $isImage,
                    'path' => $file_path,
                    'url' => $file_url,
                    'mime' => $mime,
                    'size' => $mime && Storage::exists($file_path) ? Storage::size($file_path) : 0,
                    ]];
                }
            },
        );
    }

    /**
     * Register all required dependencies here
     *
     * @return void
     */
    public function registerFileable(): void
    {
        $this->fileableLoader('file', 'default');
    }

    /**
     * All fileable properties should be registered
     *
     * @param  string|array  $file_name   filename | [filename => collection]
     * @param  string  $collection
     * @return void
     */
    public function fileableLoader(string|array $file_name = 'file', string $collection = 'default', $applyDefault = false)
    {
        $this->applyDefault = $applyDefault;

        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                if (is_array($collection)) {
                    throw new \ErrorException("Your collection should be a string");
                }

                $collect = Arr::get((new Media())->namespaces, $collection);

                if (! in_array($collection, array_keys((new Media())->namespaces)) && !$collect) {
                    throw new \ErrorException("$collection is not a valid collection");
                }
            }
        }

        if (is_array($collection)) {
            throw new \ErrorException("Your collection should be a string");
        }

        $collect = Arr::get((new Media())->namespaces, $collection);

        if (! in_array($collection, array_keys((new Media())->namespaces)) && !$collect) {
            throw new \ErrorException("$collection is not a valid collection");
        }

        $this->collection = $collection;
        $this->file_name = $file_name;
        self::$static_collection = $this->collection;
        self::$static_file_name = $this->file_name;
    }

    /**
     * Add an image to the storage media collection
     *
     * @param  string|array  $request_file_name
     * @param  string  $collection
     */
    public function saveImage(string|array $file_name = null, string $collection = 'default')
    {
        $file_name = $file_name ?? $this->file_name;
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                $save_name = (new Media())->save($collection, $file, $this->{$file});
                $this->{$file} = $save_name;
                $this->saveQuietly();
            }
        } else {
            $save_name = (new Media())->save($collection, $file_name, $this->{$file_name});
            $this->{$file_name} = $save_name;
            $this->saveQuietly();
        }
        static::uploadComplete($this);
    }

    /**
     * Load an image from the storage media collection
     *
     * @param  string  $file_name
     * @param  string  $collection
     * @param  bool  $getPath
     */
    public function retrieveFile(string $file_name = 'file', string $collection = 'default', bool $returnPath = false)
    {
        if ($this->{$file_name}) {
            return (new Media())->getMedia($collection, $this->{$file_name}, $returnPath);
        }

        if ($this->applyDefault) {
            return (new Media())->getDefaultMedia($collection);
        }

        return null;
    }

    /**
     * Delete an image from the storage media collection
     *
     * @param  string|array  $file_name
     * @param  string  $collection
     */
    public function removeFile(string|array $file_name = null, string $collection = 'default')
    {
        $file_name = $file_name ?? $this->file_name;
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                return (new Media())->delete($collection, $this->{$file});
            }
        } else {
            return (new Media())->delete($collection, $this->{$file_name});
        }
    }
}
