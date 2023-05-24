<?php

return [
    'collections' => [
        'public' => [
            'path' => '/',
            'default' => 'default.jpg',
        ],
        'avatar' => [
            'size' => [400, 400],
            'path' => 'avatars/',
            'default' => 'default.png',
        ],
        'default' => [
            'path' => 'media/default/',
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
    'file_route_secure' => 'secure/files/{file}',
    'file_route_open' => 'open/files/{file}',
    'image_templates' => [
    ],
    'symlinks' => [
        public_path('avatars') => storage_path('app/public/avatars'),
        public_path('media') => storage_path('app/public/media'),
    ],
];
