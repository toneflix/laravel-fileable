<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class Initiator
{
    /**
     * Load full paths to all collections
     *
     * @return Collection<TKey, TValue>
     */
    public static function collectionPaths(): array | Collection
    {
        $deepPaths = collect(config('toneflix-fileable.collections', []))->map(function($col, $key) {
            $getPath = Arr::get(config('toneflix-fileable.collections', []), $key.'.path');
            $prefix = str($key)->contains('private') ? '/' : '/public/';

            if (!isset($col['path'])) {
                return collect($col)->filter(fn($k)=>isset($k['path']))->map(function($k, $path) use ($key, $prefix) {
                    $getPath = Arr::get(config('toneflix-fileable.collections', []), $key . '.' . $path . '.path');
                    return Storage::path($prefix . $getPath);
                })->values();
            }

            return Storage::path($prefix . $getPath);
        });

        return $deepPaths->flatten()->unique();
    }
}
