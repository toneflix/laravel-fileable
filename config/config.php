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
            ],
            'images' => [
                'path' => 'files/images/',
                'default' => 'default.png',
            ],
            'videos' => [
                'path' => 'files/videos/',
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
    'secure_file_middleware' => null,
    'secure_file_route' => 'secure/files/{file}',
    'image_templates' => [
    ],
    'symlinks' => [
        public_path('avatars') => storage_path('app/public/avatars'),
        public_path('media') => storage_path('app/public/media'),
    ]
];