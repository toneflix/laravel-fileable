# Laravel Fileable

[![Test & Lint](https://github.com/toneflix/laravel-fileable/actions/workflows/run-tests.yml/badge.svg?branch=main)](https://github.com/toneflix/laravel-fileable/actions/workflows/run-tests.yml)
[![Latest Stable Version](http://poser.pugx.org/toneflix-code/laravel-fileable/v)](https://packagist.org/packages/toneflix-code/laravel-fileable) [![Total Downloads](http://poser.pugx.org/toneflix-code/laravel-fileable/downloads)](https://packagist.org/packages/toneflix-code/laravel-fileable) [![Latest Unstable Version](http://poser.pugx.org/toneflix-code/laravel-fileable/v/unstable)](https://packagist.org/packages/toneflix-code/laravel-fileable) [![License](http://poser.pugx.org/toneflix-code/laravel-fileable/license)](https://packagist.org/packages/toneflix-code/laravel-fileable) [![PHP Version Require](http://poser.pugx.org/toneflix-code/laravel-fileable/require/php)](https://packagist.org/packages/toneflix-code/laravel-fileable)
[![codecov](https://codecov.io/gh/toneflix/laravel-fileable/graph/badge.svg?token=2O7aFulQ9P)](https://codecov.io/gh/toneflix/laravel-fileable)

<!-- ![GitHub Actions](https://github.com/toneflix/laravel-fileable/actions/workflows/main.yml/badge.svg) -->

Laravel Fileable exposes methods that make handling file upload with Laravel filesystem even easier, it also exposes a trait that automatically handles file uploads for you with support for uploading base64 encoded files.

## Installation

You can install the package via composer:

```bash
composer require toneflix-code/laravel-fileable
```

## Package Discovery

Laravel automatically discovers and publishes service providers but optionally after you have installed Laravel Fileable, open your Laravel config file config/app.php and add the following lines.

In the $providers array add the service providers for this package.

```php
ToneflixCode\LaravelFileable\FileableServiceProvider::class
```

Add the facade of this package to the $aliases array.

```php
'Fileable' => ToneflixCode\LaravelFileable\Facades\Fileable::class
```

## Upgrading

Version 2.x is not compatible with version 1.x, if you are ugrading from version 1.x here are a few notes:

### Config

1. If you published the configuration file, remove `image_templates`. Templates are no longer needed, just set you responsive image sizes using the `image_sizes` property.

2. Add `responsive_image_route` and set the value `route/path/{file}/{size}`, `route/path` can be whatever you want it to be, `{file}/{size}` can be anything you want to name them but both are required.

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

### fileableLoader.

The `fileableLoader` is responsible for mapping your model to the required collection and indicates that you want to use Laravel Filable to manage your model files.

The `fileableLoader()` method accepts an array of `[key => value]` pairs that determines which files should be auto discovered in your request, the `key` should match the name field in your input field E.g `<input type="file" name="avatar">`, the `value` should be an existing collection in your Laravel Fileable configuration.

#### Single collection initialization.

```php
$this->fileableLoader([
    'avatar' => 'avatar',
]);
```

#### Multiple collection initialization.

```php
$this->fileableLoader([
    'avatar' => 'avatar',
    'image' => 'default',
]);
```

#### String parameter initialization.

The `fileableLoader()` method also accepts the `key` as a string first parameter and the `value` as a string as the second parameter.

```php
$this->fileableLoader('avatar', 'default');
```

#### Default media.

COnfigured default files are not loaded by default, to load the default file for the model, the `fileableLoader` exposes a third parameter, the `useDefault` parameter, setting it to true will ensure that your default file is loaded when the model's file is not found or missing.

```php
$this->fileableLoader('avatar', 'default', true);
```

OR

```php
$this->fileableLoader([
    'avatar' => 'avatar',
], 'default', true);
```

#### Supporting old setup (Legacy Mode)

If you had your model running before the introducation of the the Fileable trait, you might still be able to load your existing files by passing a fourth parameter to the `fileableLoader()`, the **Legacy mode** attempts to load media files that had been stored or managed by a different logic or system before the introduction of the fileable trait.

```php
$this->fileableLoader('avatar', 'default', true, true);
```

OR

```php
$this->fileableLoader([
    'avatar' => 'avatar',
], 'default', true, true);
```

#### Custom Database field.

There are times when you may want to use a different file name from your database field name, an instance could be when your request includes two diffrent file requests for different models that have the same database field names, the last parameter of the `fileableLoader` was added to support this scenario.

The 5th parameter of the `fileableLoader` is a string that should equal to the database field where you want your file reference stored in or an array that maps the request file name to the database field name.

Take a look at this example.

```html
<input name="cover" type="file" /> <input name="admin_avatar" type="file" />
```

```php
$this->fileableLoader('admin_avatar', 'default', true, true, 'image');
```

OR

```php
$this->fileableLoader([
    'admin_avatar' => 'avatar',
], 'default', true, true, 'image');
```

OR

```php
$this->fileableLoader([
    'cover' => 'cover',
    'admin_avatar' => 'avatar',
], 'default', true, true, [
    'cover' => 'cover_image',
    'admin_avatar' => 'image',
]);
```

In the last example, `cover_image` is an existing database field mapped to the `cover` input request file name and `image` is an existing database field mapped to the `admin_avatar` input request file name.

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

#### mediaFile()

Returns a single media link from list of all bound files (Usefull especially when you are binding only a single resource)

```php
$user = User::first();
$avatar = $user->media_file;
var_dump($avatar);
```

#### mediaFileInfo()

Returns the attribute of a single media file (Usefull especially when you are binding only a single resource)

```php
$post = Post::first();
var_dump($post->media_file_info);
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

If the registered files are images this attribute exposes responsive images for them or returns the defual image

```php
$user = User::first();
var_dump($user->responsive_images);
var_dump($user->responsive_images['avatar']);

$post = Post::first();
var_dump($post->responsive_images['image']);
var_dump($post->responsive_images['banner']);
```

#### Prefixed Media Collections

While the library will try to resolve media files from the configured collection, you can also force media file search from collections different from the configured ones by saving the path reference on the database with a `collection:filename.ext` prefix, this will allow the system to look for media files in a collection named `collection` even if the current collection for the model is a collection named `images`;

### Manually saving files

You can also skip the interface and use the `save` method of `Media` library to manually create your files, the `save` method returns the name of the file as saved in your configured storage.

```php
use ToneflixCode\LaravelFileable\Media;
use App\Models\User;

$user = User::find(1);

$user->image = (new Media())->save('media', "image", $user->image);
$user->saveQuietly();
```

In the above example `media` is the target collection where your file should be saved while `image` is the name of the input from the request holding your file.

#### Manually saving from wildcard request array.

Consider the following scenario in a controller.

```php
['forms' => $forms] = $this->validate($request, [
    'users.*.id' => ['required', 'exists:users,id'],
    'users.*.image' => ['nullable', 'image', 'mimes:png,jpg'],
]);

foreach ($forms as $i => $form) {
    $user = User::find($form['id']);

    $user->image = (new Media())->save('media', "users.*.image", $user->image, $i);
    $user->saveQuietly();
}
```

What we have done is save the files from within a loop, the 4th parameter of the `save` method [`index`] indicates where the file we want to save can be found in the requests uploaded file list.

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

- [Toneflix Code](https://github.com/toneflix)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
