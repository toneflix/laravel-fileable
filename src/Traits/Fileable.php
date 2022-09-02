<?php

namespace ToneflixCode\LaravelFileable\Traits;

use ToneflixCode\LaravelFileable\Media;
use Illuminate\Database\Eloquent\Casts\Attribute;

/**
 * A collection of usefull model manipulation classes.
 */
trait Fileable
{
    public array $sizes;

    public string $collection = 'image';

    public string|array $file_name = 'file';

    public static string $static_collection = 'image';

    public static string|array $static_file_name = 'file';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->sizes = config('toneflix-fileable.image_sizes');
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
                    $item->removeImage($file, $collection);
                });
            }

            return $images;
        } else {
            static::saved(function ($item) {
                $item->saveImage(self::$static_file_name, self::$static_collection);
            });

            static::deleting(function ($item) {
                $item->removeImage(self::$static_file_name, self::$static_collection);
            });
        }
    }

    public function images(): Attribute
    {
        return new Attribute(
            get: function () {
                if (is_array($this->file_name)) {
                    $images = [];
                    foreach ($this->file_name as $file => $collection) {
                        $images[$file] = $this->retrieveImage($file, $collection);
                    }

                    return $images;
                } else {
                    return [$this->file_name] = $this->retrieveImage($this->file_name, $this->collection);
                }
            },
        );
    }

    public function defaultImage(): Attribute
    {
        return Attribute::make(
            get: fn () => asset('media/default.jpg')
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
                            $asset = pathinfo($this->retrieveImage($file, $collection), PATHINFO_BASENAME);

                            return [$key => route('imagecache', [$size, $asset])];
                        });
                    }

                    return $images;
                } else {
                    return collect($this->sizes)->mapWithKeys(function ($size, $key) {
                        $asset = pathinfo($this->retrieveImage($this->file_name, $this->collection), PATHINFO_BASENAME);

                        return [$key = route('imagecache', [$size, $asset])];
                    });
                }
            },
        );
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
    public function fileableLoader(string|array $file_name = 'file', string $collection = 'default')
    {
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                if (! in_array($collection, array_keys((new Media)->namespaces))) {
                    throw new \ErrorException("$collection is not a valid collection");
                }
            }
        }

        if (! in_array($collection, array_keys((new Media)->namespaces))) {
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
                $save_name = (new Media)->save($collection, $file, $this->{$file});
                $this->{$file} = $save_name;
                $this->saveQuietly();
            }
        } else {
            $save_name = (new Media)->save($collection, $file_name, $this->{$file_name});
            $this->{$file_name} = $save_name;
            $this->saveQuietly();
        }
    }

    /**
     * Load an image from the storage media collection
     *
     * @param  string  $file_name
     * @param  string  $collection
     */
    public function retrieveImage(string $file_name = 'file', string $collection = 'default')
    {
        return (new Media)->image($collection, $this->{$file_name}, $this->default_image);
    }

    /**
     * Delete an image from the storage media collection
     *
     * @param  string|array  $file_name
     * @param  string  $collection
     */
    public function removeImage(string|array $file_name = null, string $collection = 'default')
    {
        $file_name = $file_name ?? $this->file_name;
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                return (new Media)->delete($collection, $this->{$file});
            }
        } else {
            return (new Media)->delete($collection, $this->{$file_name});
        }
    }
}