# Laravel Fileable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/toneflix-code/laravel-fileable.svg?style=flat-round)](https://packagist.org/packages/toneflix-code/laravel-fileable)
[![Total Downloads](https://img.shields.io/packagist/dt/toneflix-code/laravel-fileable.svg?style=flat-round)](https://packagist.org/packages/toneflix-code/laravel-fileable)
<!-- ![GitHub Actions](https://github.com/toneflix/laravel-fileable/actions/workflows/main.yml/badge.svg) -->

Laravel Fileable exposes methods that make handling file upload with Laravel filesystem even easier, it also exposes a trait that automatically handles file uploads for you.

## Installation

You can install the package via composer:

```bash
composer require toneflix-code/laravel-fileable
```

## Installation

Laravel automatically discovers and publishes service providers but optionally after you have installed Laravel Fileable, open your Laravel config file config/app.php and add the following lines.

In the $providers array add the service providers for this package.

```php
ToneflixCode\LaravelFileable\FileableServiceProvider::class
```

Add the facade of this package to the $aliases array.

```php
'Fileable' => ToneflixCode\LaravelFileable\Facades\Fileable::class
```

## Configuration

By default Laravel Fileable `avatar` and `media` directories and symlinks to your `storage/app/public` directories, and also adds the `file` directory to your `storage/app` directory.
You may change this or decide to modify the directories that will be created by running the following artisan command.

```bash
php artisan vendor:publish --provider="ToneflixCode\LaravelFileable\FileableServiceProvider"
``` 

The configuration file is copied to config/toneflix-fileable.php. With this copy you can alter the settings for your application locally.

### Generating symlinks

After publishing and modifying the configuration, both of which are optional you will need to run the following artisan command to actually generate the required symlinks by running the following artisan command.

```bash
php artisan storage:link
``` 

### Collections

The `collection` config option define where files should be stored and optionally a default file that should be returned when the requested file is not found.

### Image Sizes

This package uses [Intervention Imagecache](https://github.com/Intervention/imagecache) to generate responsive images for image files on demand, the `image_sizes` config option defines which responsive sizes to generate, you are not limited to use the defined sizes, take a look at [Intervention Imagecache Documentation](https://image.intervention.io/v2/usage/cache) for information about customizsing this feature.

### File Route Secure

The `file_route_secure` config option sets the route from which secure images should be loaded from. The route accepts one parameter, the `{file}` parameter.

### File Route Open

The `file_route_open` config option sets the route from which secure images which do not require authentication or authorization should be loaded from. The route accepts one parameter, the `{file}` parameter.

### Secure File Middleware

The `file_route_secure_middleware` config option sets which middleware to apply when using the protected files collection.

### Symlinks

The `symlinks` option maps where [Intervention Imagecache](https://github.com/Intervention/imagecache) should search for images in your app, this does not overide your current [Intervention Imagecache](https://github.com/Intervention/imagecache) configuration, it appends. 

### Image Templates

The `image_templates` option generates image filters based on [Intervention Imagecache](https://github.com/Intervention/imagecache) templates, this also does not overide your current [Intervention Imagecache](https://github.com/Intervention/imagecache) configuration, it appends. 

## Usage

To automatically discover files in request and save them to storage and database you will need to add the `ToneflixCode\LaravelFileable\Traits\Fileable` trait to your models and register the required filables using the `fileableLoader()` method from the `ToneflixCode\LaravelFileable\Traits\Fileable` trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use ToneflixCode\LaravelFileable\Traits\Fileable;

class User extends Model
{
    use HasFactory, Fileable;

    public function registerFileable()
    {
        $this->fileableLoader([
            'avatar' => 'default',
        ]);
    }
}

```

The `fileableLoader()` method accepts and array of `[key => value]` pairs that determines which files should be auto discovered in your request, the `key` should match the name field in your input field E.g `<input type="file" name="avatar">`, the `value` should be an existing collection in your Laravel Fileable configuration.

```php
$this->fileableLoader([
    'avatar' => 'avatar',
]);
```

OR

```php
$this->fileableLoader([
    'avatar' => 'avatar',
    'image' => 'default',
]);
```

The `fileableLoader()` method also accepts the `key` as a string as the first parameter and the `value` as a string as the second parameter.

```php
$this->fileableLoader('avatar', 'default');
```

### Model Events

If you use listen to laravel events via the `boot()` you would need to move your event handles to the `registerEvents()` method of the `ToneflixCode\LaravelFileable\Traits\Fileable` trait.

This should be defined in your model to overide the default handles.

```php
public static function registerEvents()
{
    static::creating(function ($item) {
        $item->slug = str($item->title)->slug();
    });
}
```

### Model Attributes

Laravel Fileable exposes 3 model Attributes which will help with accessing your saved files

#### defaultImage()

This attribute exposes the default image of the `ToneflixCode\LaravelFileable\Traits\Fileable` trait

Depending on the collections you have created, you may need to add the default image file to the respective directories within the collections.

```php
$post = Post::first();
var_dump($post->default_image);
```

#### getFiles()

Returns a list of bound files with a little more details like mime, isImage, url, path and size

```php
$post = Post::first();
var_dump($post->get_files);
var_dump($user->get_files['image']);
```

#### files()

This attribute exposes all images registered with the `fileableLoader()` method of the `ToneflixCode\LaravelFileable\Traits\Fileable` trait

```php
$user = User::first();
var_dump($user->files);
var_dump($user->files['avatar']);

$post = Post::first();
var_dump($post->files['image']);
```

#### responsiveImages()

If the registered files are images his attribute exposes responsive images for them or returns the defual image

```php
$user = User::first();
var_dump($user->responsive_images);
var_dump($user->responsive_images['avatar']);

$post = Post::first();
var_dump($post->responsive_images['image']);
var_dump($post->responsive_images['banner']);
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email code@toneflix.com.ng instead of using the issue tracker.

## Credits

-   [Toneflix Code](https://github.com/toneflix)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
