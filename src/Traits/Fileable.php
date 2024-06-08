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
     * If this is an array, the field should be mapped to the $file_name property
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
     * @var string|array<string,string>
     */
    public string|array $file_name = 'file';

    /**
     * Legacy mode is used to support media files that were saved before the introduction of the fileable trait
     */
    protected bool $legacyMode = false;

    /**
     * Apply default file if no file is found
     * If set to false missing files will not be replaced with the default URL
     */
    protected bool $applyDefault = false;

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

        static::saved(function (Fileable|Model $model) {
            if (is_array($model->file_name)) {
                foreach ($model->file_name as $file => $collection) {
                    $model->saveImage($file, $collection);
                }
            } else {
                $model->saveImage($model->file_name, $model->collection);
            }
        });

        static::deleting(function (Fileable|Model $model) {
            if (is_array($model->file_name)) {
                foreach ($model->file_name as $file => $collection) {
                    $model->removeFile($file, $collection);
                }
            } else {
                $model->removeFile($model->file_name, $model->collection);
            }
        });
    }

    /**
     *  Returns a list of all bound files.
     *
     * @deprecated  1.0.0    Use files instead, will be removed from future versions
     */
    public function images(): Attribute
    {
        return new Attribute(
            get: fn () => $this->files,
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
                ? (new Media($this->disk))->getDefaultMedia($this->collection)
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
                            $prefix = !str($collection)->contains('private.') ? 'public/' : '/';

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
                        $prefix = !str($this->collection)->contains('private.') ? 'public/' : '/';
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
                        $prefix = !str($collection)->contains('private.') ? 'public/' : '/';
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
                    $prefix = !str($this->collection)->contains('private.') ? 'public/' : '/';
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
     */
    public function registerFileable(): void
    {
        $this->fileableLoader('file', 'default');
    }

    /**
     * All fileable properties should be registered
     *
     * @param  string|array<string,string>  $file_name   filename | [filename => collection]
     * @param  string  $collection  The name of the collection where files for the model should be stored in
     * @param  string  $applyDefault  If set to false missing files will not be replaced with the default URL
     * @param  bool  $legacyMode Support media files that were saved before the introduction of the fileable trait
     * @param  string|array|null  $db_field  The field in the DB where the file reference should be saved
     * @return void
     */
    public function fileableLoader(
        string|array $file_name = 'file',
        string $collection = 'default',
        bool $applyDefault = false,
        bool $legacyMode = false,
        string|array $db_field = null,
    ) {
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                if (is_array($collection)) {
                    throw new \ErrorException('Your collection should be a string');
                }

                $collect = Arr::get((new Media($this->disk))->namespaces, $collection);

                if (!in_array($collection, array_keys((new Media($this->disk))->namespaces)) && !$collect) {
                    throw new \ErrorException("$collection is not a valid collection");
                }
            }
        }

        if (is_array($collection)) {
            throw new \ErrorException('Your collection should be a string');
        }

        $collect = Arr::get((new Media($this->disk))->namespaces, $collection);

        if (!in_array($collection, array_keys((new Media($this->disk))->namespaces)) && !$collect) {
            throw new \ErrorException("$collection is not a valid collection");
        }
        $this->applyDefault = $applyDefault;
        $this->legacyMode = $legacyMode;
        $this->collection = $collection;
        $this->file_name = $file_name;
        $this->db_field = $db_field ?? $this->db_field;
    }

    /**
     * Add an image to the storage media collection
     *
     * @param  string|array  $request_file_name
     */
    public function saveImage(string|array $file_name = null, string $collection = 'default')
    {
        $request = request();

        $file_name = $file_name ?? $this->file_name;
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                if ($this->checkBase64($request->get($file))) {
                    $save_name = (new Media($this->disk))
                        ->saveEncoded($collection, $request->get($file), $this->{$this->getFieldName($file)});
                } else {
                    $save_name = (new Media($this->disk))
                        ->save($collection, $file, $this->{$this->getFieldName($file)});
                }
                // This maps to $this->image = $save_name where image is an existing database field
                $this->{$this->getFieldName($file)} = $save_name;
                $this->saveQuietly();
            }
        } else {
            if ($this->checkBase64($request->get($file_name))) {
                $save_name = (new Media($this->disk))
                    ->saveEncoded($collection, $request->get($file_name), $this->{$this->getFieldName($file_name)});
            } else {
                $save_name = (new Media($this->disk))
                    ->save($collection, $file_name, $this->{$this->getFieldName($file_name)});
            }
            // This maps to $this->image = $save_name where image is an existing database field
            $this->{$this->getFieldName($file_name)} = $save_name;
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
    public function retrieveFile(string $file_name = 'file', string $collection = 'default', bool $returnPath = false)
    {
        if ($this->getFieldName($file_name)) {
            return (new Media($this->disk))
                ->getMedia($collection, $this->{$this->getFieldName($file_name)}, $returnPath);
        }

        if ($this->applyDefault) {
            return (new Media($this->disk))->getDefaultMedia($collection);
        }

        return null;
    }

    /**
     * Delete an image from the storage media collection
     */
    public function removeFile(string|array $file_name = null, string $collection = 'default')
    {
        $file_name = $file_name ?? $this->file_name;
        if (is_array($file_name)) {
            foreach ($file_name as $file => $collection) {
                return (new Media($this->disk))
                    ->delete($collection, $this->{$this->getFieldName($file)});
            }
        } else {
            return (new Media($this->disk))
                ->delete($collection, $this->{$this->getFieldName($file_name)});
        }
    }

    protected function getFieldName(string $file_name): string
    {
        if (!$this->db_field) {
            return $file_name;
        }

        if (is_array($this->db_field)) {
            return $this->db_field[$file_name] ?? $file_name;
        }

        return $this->db_field ?? $file_name;
    }
}
