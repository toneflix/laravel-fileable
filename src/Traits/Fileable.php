<?php

namespace ToneflixCode\LaravelFileable\Traits;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use ToneflixCode\LaravelFileable\Media;

/**
 * A collection of usefull model manipulation classes.
 */
trait Fileable
{
    public $namespaces;

    /**
     * THe prefered disk for this instance
     */
    public ?string $disk = null;

    /**
     * An array of responsive image breakpoints
     *
     * @var array<string,string>
     */
    public array $sizes;

    /**
     * The name of the collection where files for the model should be stored in
     */
    public string $collection = 'image';

    /**
     * The field in the DB where the file reference should be saved
     * If this is an array, the field should be mapped to the $file_field property
     *
     * @example db_field $db_field = ['image' => 'avatar'];
     * In this case avatar is an existing DB field and image will be the filename from the request
     *
     * @var string|array<string,string>|null
     */
    public string|array|null $db_field = '';

    /**
     * An array mapping of file name (file request param) and colletion name
     * Or a file name (file request param) string
     *
     * @deprecated 2.0.3 Use $file_field instead.
     *
     * @var string|array<string,string>
     */
    public string|array $file_name = 'file';

    /**
     * An array mapping of file name (file request param) and colletion name
     * Or a file name (file request param) string
     *
     * @var string|array<string,string>
     */
    public string|array $file_field = 'file';

    /**
     * Apply default file if no file is found
     * If set to false missing files will not be replaced with the default URL
     */
    protected bool $applyDefault = false;

    /**
     * Legacy mode is used to support media files that were saved before the introduction of the fileable trait
     */
    protected bool $legacyMode = false;

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

        /**
         * register the fileable in the retrieved, creating, updating and saving 
         * events to allow for model based configuration.
         */
        static::retrieved(fn(Fileable|Model $m) => $m->registerFileable());
        static::creating(fn(Fileable|Model $m) => $m->registerFileable());
        static::updating(fn(Fileable|Model $m) => $m->registerFileable());
        static::saving(fn(Fileable|Model $m) => $m->registerFileable());

        static::saved(function (Fileable|Model $model) {
            if (is_array($model->file_field)) {
                foreach ($model->file_field as $field => $collection) {
                    $model->saveImage($field, $collection);
                }
            } else {
                $model->saveImage($model->file_field, $model->collection);
            }
        });

        static::deleting(function (Fileable|Model $model) {
            if (is_array($model->file_field)) {
                foreach ($model->file_field as $field => $collection) {
                    $model->removeFile($field, $collection);
                }
            } else {
                $model->removeFile($model->file_field, $model->collection);
            }
        });

        static::registerEvents();
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
                if (is_array($this->file_field)) {
                    $files = [];
                    foreach ($this->file_field as $field => $collection) {
                        $files[$field] = $this->retrieveFile($field, $collection);
                    }

                    return $files;
                } else {
                    return [$this->file_field => $this->retrieveFile($this->file_field, $this->collection)];
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
                // If the file name is an array, get the first file
                if (is_array($this->file_field)) {
                    foreach ($this->file_field as $field => $collection) {
                        $file_field = $field;
                        $collection = $collection;
                        break;
                    }
                } else {
                    $file_field = $this->file_field;
                    $collection = $this->collection;
                }

                return $this->retrieveFile($file_field, $collection) ?? (new Media)->getDefaultMedia($collection);
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
                // If the file name is an array, get the first file
                if (is_array($this->file_field)) {
                    foreach ($this->file_field as $field => $collection) {
                        $file_field = $field;
                        $collection = $collection;
                        break;
                    }
                } else {
                    $file_field = $this->file_field;
                    $collection = $this->collection;
                }

                return [
                    $file_field => (new Media)->mediaInfo($collection, $this->{$this->getFieldName($file_field)}),
                ];
            },
        );
    }

    /**
     *  Returns the default image for the model as an attribute
     */
    public function defaultImage(): Attribute
    {
        return Attribute::make(
            get: fn() => ($this->collection
                ? (new Media($this->disk))->getDefaultMedia($this->collection)
                : asset('media/default.jpg')
            )
        );
    }

    public function responsiveImages(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (is_array($this->file_field)) {
                    $images = [];
                    foreach ($this->file_field as $field => $collection) {
                        $images[$field] = collect($this->sizes)->mapWithKeys(function ($size, $key) use ($field, $collection) {
                            $prefix = ! str($collection)->contains('private.') ? 'public/' : '/';

                            $isImage = str(Storage::mimeType($prefix . $this->retrieveFile($field, $collection, true)))
                                ->contains('image');

                            if (! $isImage) {
                                return [$key => $this->default_image];
                            }

                            $asset = pathinfo($this->retrieveFile($field, $collection), PATHINFO_BASENAME);

                            return [$key => route('imagecache', [$size, $asset])];
                        });
                    }

                    return $images;
                } else {
                    return collect($this->sizes)->mapWithKeys(function ($size, $key) {
                        $prefix = ! str($this->collection)->contains('private.') ? 'public/' : '/';
                        $isImage = str(Storage::mimeType($prefix . $this->retrieveFile($this->file_field, $this->collection, true)))
                            ->contains('image');

                        if (! $isImage) {
                            return [$key => $this->default_image];
                        }

                        $asset = pathinfo($this->retrieveFile($this->file_field, $this->collection), PATHINFO_BASENAME);

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
                if (is_array($this->file_field)) {
                    $files = [];
                    foreach ($this->file_field as $field => $collection) {
                        $files[$field] = (new Media)->mediaInfo($collection, $this->{$this->getFieldName($field)});
                    }

                    return $files;
                } else {
                    return [
                        $this->file_field => (new Media)->mediaInfo(
                            $this->collection,
                            $this->{$this->getFieldName($this->file_field)}
                        ),
                    ];
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
    public static function registerEvents() {}

    /**
     * Register all required dependencies here
     */
    public function registerFileable(): void
    {
        $this->fileableLoader('file', 'default');
    }

    /**
     * All fileable properties should be registered
     *
     * @param  string|array<string,string>  $file_field  filename | [filename => collection]
     * @param  string  $collection  The name of the collection where files for the model should be stored in
     * @param  string  $applyDefault  If set to false missing files will not be replaced with the default URL
     * @param  bool  $legacyMode  Support media files that were saved before the introduction of the fileable trait
     * @param  string|array|null  $db_field  The field in the DB where the file reference should be saved
     * @return void
     */
    public function fileableLoader(
        string|array $file_field = 'file',
        string $collection = 'default',
        bool $applyDefault = false,
        bool $legacyMode = false,
        string|array|null $db_field = null,
    ) {
        if (is_array($file_field)) {
            foreach ($file_field as $field => $collection) {
                if (is_array($collection)) {
                    throw new \ErrorException('Your collection should be a string');
                }

                $collect = Arr::get((new Media($this->disk))->namespaces, $collection);

                if (! in_array($collection, array_keys((new Media($this->disk))->namespaces)) && ! $collect) {
                    throw new \ErrorException("$collection is not a valid collection");
                }
            }
        }

        if (is_array($collection)) {
            throw new \ErrorException('Your collection should be a string');
        }

        $collect = Arr::get((new Media($this->disk))->namespaces, $collection);

        if (! in_array($collection, array_keys((new Media($this->disk))->namespaces)) && ! $collect) {
            throw new \ErrorException("$collection is not a valid collection");
        }
        $this->applyDefault = $applyDefault;
        $this->legacyMode = $legacyMode;
        $this->collection = $collection;
        $this->file_field = $file_field;
        $this->file_name = $file_field;
        $this->db_field = $db_field ?? $this->db_field;
    }

    /**
     * Add an image to the storage media collection
     */
    public function saveImage(string|array|null $file_field = null, string $collection = 'default')
    {
        $request = request(null);

        $file_field = $file_field ?? $this->file_field;
        if (is_array($file_field)) {
            foreach ($file_field as $field => $collection) {
                if ($this->checkBase64($request->get($field))) {
                    $save_name = (new Media($this->disk))
                        ->saveEncoded($collection, $request->get($field), $this->{$this->getFieldName($field)});
                } else {
                    $save_name = (new Media($this->disk))
                        ->save($collection, $field, $this->{$this->getFieldName($field)});
                }
                // This maps to $this->image = $save_name where image is an existing database field
                $this->{$this->getFieldName($field)} = $save_name;
                $this->saveQuietly();
            }
        } else {
            if ($this->checkBase64($request->get($file_field))) {
                $save_name = (new Media($this->disk))
                    ->saveEncoded($collection, $request->get($file_field), $this->{$this->getFieldName($file_field)});
            } else {
                $save_name = (new Media($this->disk))
                    ->save($collection, $file_field, $this->{$this->getFieldName($file_field)});
            }
            // This maps to $this->image = $save_name where image is an existing database field
            $this->{$this->getFieldName($file_field)} = $save_name;
            $this->saveQuietly();
        }
    }

    public function checkBase64($file): bool
    {
        return (bool) preg_match('/^data:image\/(\w+);base64,/', $file);
    }

    /**
     * Load an image from the storage media collection
     *
     * @param  bool  $getPath
     */
    public function retrieveFile(string $file_field = 'file', string $collection = 'default', bool $returnPath = false)
    {
        if ($this->getFieldName($file_field)) {
            return (new Media($this->disk))
                ->getMedia($collection, $this->{$this->getFieldName($file_field)}, $returnPath);
        }

        if ($this->applyDefault) {
            return (new Media($this->disk))->getDefaultMedia($collection);
        }

        return null;
    }

    /**
     * Delete an image from the storage media collection
     */
    public function removeFile(string|array|null $file_field = null, string $collection = 'default')
    {
        $file_field = $file_field ?? $this->file_field;

        if (is_array($file_field)) {
            foreach ($file_field as $field => $collection) {
                return (new Media($this->disk))
                    ->delete($collection, $this->{$this->getFieldName($field)});
            }
        } else {
            return (new Media($this->disk))
                ->delete($collection, $this->{$this->getFieldName($file_field)});
        }
    }

    protected function getFieldName(string $file_field): string
    {
        if (! $this->db_field) {
            return $file_field;
        }

        if (is_array($this->db_field)) {
            return $this->db_field[$file_field] ?? $file_field;
        }

        return $this->db_field ?? $file_field;
    }
}
