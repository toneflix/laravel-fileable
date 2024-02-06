<?php

return [
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
    'image_sizes' => [
        'xs' => '431',
        'sm' => '431',
        'md' => '694',
        'lg' => '720',
        'xl' => '1080',
    ],
    'file_route_secure_middleware' => 'window_auth',
    'responsive_image_route' => 'images/responsive',
    'file_route_secure' => 'secure/files/{file}',
    'file_route_open' => 'open/files/{file}',
    'image_templates' => [
        '431' => [431, 767],
        '694' => [694, 521],
        '720' => [720, 405],
        '1080' => [1080, 767],
    ],
    'symlinks' => [
        public_path('avatars') => storage_path('app/public/avatars'),
        public_path('media') => storage_path('app/public/media'),
    ],
];