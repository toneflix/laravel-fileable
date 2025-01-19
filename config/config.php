<?php

//
return [

    /*
     |--------------------------------------------------------------------------
     | File Name Pattern
     |--------------------------------------------------------------------------
     |
     | When saving files, we will use this partern to generate the file name.
     |
     | Key:
     |	A: Random uppercase letter.
     |	a: Random lowercase letter.
     |	0: Random digit (0–9).
     |	-: A fixed hyphen.
     |	X: Random alphanumeric character (both letters and digits).
     |
     | E.g. AAA-000000000-XXX
     |
    */

    'file_name_pattern' => '000000000_000000000',

    /*
     |--------------------------------------------------------------------------
     | Streamable Mimes
     |--------------------------------------------------------------------------
     |
     | When accessing a remote url via any of the Dynamic Route endpoints
     | the system will attempt to stream the file if it's mime matches
     | any of the mimes in the config.
     |
     | E.g. ['video/mp4', 'audio/mp3']
     |
    */

    'streamable_mimes' => [
        'video/mp4',
        'audio/mp3',
    ],

    /*
     |--------------------------------------------------------------------------
     | Decode Remote Files
     |--------------------------------------------------------------------------
     |
     | When accessing a remote url via any of the Dynamic Route endpoints
     | the system will attempt to fetch and processs the file from the origin
     | before serving it to the user if this is set to true, otherwise
     | request will fail and return 404
     |
     | ** Not recommeded for production use.
     |
    */

    // TODO: Implement this
    'decode_remote_files' => true,
    // Not yet implemented

    /*
    |--------------------------------------------------------------------------
    | Private Dynamic Link Middleware
    |--------------------------------------------------------------------------
    |
    | The middleware that will be applied when accessing the
    | Private Dynamic Link Route
    |
    */

    'file_route_secure_middleware' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Responsive Route
    |--------------------------------------------------------------------------
    |
    | The route definition to help users access the responsive images
    |
    */

    'responsive_image_route' => 'images/responsive/{file}/{size}',

    /*
    |--------------------------------------------------------------------------
    | Private Dynamic Link Route
    |--------------------------------------------------------------------------
    |
    | The route definition to help users access the private dynamic files
    |
    */

    'file_route_secure' => 'secure/files/{file}',

    /*
    |--------------------------------------------------------------------------
    | Public Dynamic Link Route
    |--------------------------------------------------------------------------
    |
    | The route definition to help users access the public dynamic files
    |
    */

    'file_route_open' => 'open/files/{file}',

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'symlinks' => [
        public_path('avatars') => storage_path('app/public/avatars'),
        public_path('media') => storage_path('app/public/media'),
    ],

    /*
     |--------------------------------------------------------------------------
     | Collection Maps
     |--------------------------------------------------------------------------
     |
     | This is how files stored on your server or storage driver are mapped
     | to their specific URLs.
     | The [path] property of every collcetion should match the symlinks
     | defined above.
     |
    */

    'collections' => [
        'avatar' => [
            'size' => [400, 400],
            'path' => 'avatars/',
            'default' => 'default.png',
        ],
        'banner' => [
            'size' => [1200, 600],
            'path' => 'media/banners/',
            'default' => 'default.png',
        ],
        'default' => [
            'path' => 'media/default/',
            'default' => 'default.png',
        ],
        'logo' => [
            'size' => [200, 200],
            'path' => 'media/logos/',
            'default' => 'default.png',
        ],
        'private' => [
            'files' => [
                'path' => 'files/',
                'secure' => false,
            ],
            'images' => [
                'path' => 'files/images/',
                'default' => 'default.png',
                'secure' => true,
            ],
            'videos' => [
                'path' => 'files/videos/',
                'secure' => true,
            ],
        ],
    ],

    /*
     |--------------------------------------------------------------------------
     | Image Size Maps
     |--------------------------------------------------------------------------
     |
     | This will be mapped to generate responsive variants of your images
     |
    */

    'image_sizes' => [
        'xs' => '431',
        'sm' => '431',
        'md' => '694',
        'lg' => '720',
        'xl' => '1080',
        'xs-square' => '431x431',
        'sm-square' => '431x431',
        'md-square' => '694x694',
        'lg-square' => '720x720',
        'xl-square' => '1080x1080',
    ],
];
