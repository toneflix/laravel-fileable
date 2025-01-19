<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Initiator
{
    /**
     * Load full paths to all collections
     *
     * @return Collection<TKey, string|array|int|Collection>
     */
    public static function collectionPaths(): array|Collection
    {
        $deepPaths = collect(config('toneflix-fileable.collections', []))->map(function ($col, $key) {
            $getPath = Arr::get(config('toneflix-fileable.collections', []), $key.'.path');
            $prefix = str($key)->contains('private') ? '/' : '/public/';

            if (! isset($col['path'])) {
                return collect($col)->filter(fn ($k) => isset($k['path']))->map(function ($k, $path) use ($key, $prefix) {
                    $getPath = Arr::get(config('toneflix-fileable.collections', []), $key.'.'.$path.'.path');

                    return Storage::path($prefix.$getPath);
                })->values();
            }

            return Storage::path($prefix.$getPath);
        });

        return $deepPaths->flatten()->unique();
    }

    /**
     * Load public asset
     */
    public static function asset(string $url, $absolute = false): string
    {
        if ($absolute) {
            return str($url)->replace('http:', request(null)->isSecure() ? 'https:' : 'http:')->toString();
        }

        return request(null)->isSecure()
            ? secure_asset($url)
            : asset($url);
    }

    /**
     * Encode data to Base64URL
     *
     * @param  string  $data
     * @return bool|string
     */
    public static function base64urlEncode($data)
    {
        // First of all you should encode $data to Base64 string
        $b64 = base64_encode($data);

        // Make sure you get a valid result, otherwise, return FALSE, as the base64_encode() function do
        if ($b64 === false) {
            return false;
        }

        // Convert Base64 to Base64URL by replacing “+” with “-” and “/” with “_”
        $url = strtr($b64, '+/', '-_');

        // Remove padding character from the end of line and return the Base64URL result
        return rtrim($url, '=');
    }

    /**
     * Decode data from Base64URL
     *
     * @param  string  $data
     * @param  bool  $strict
     * @return bool|string
     */
    public static function base64urlDecode($data, $strict = false)
    {
        // Convert Base64URL to Base64 by replacing “-” with “+” and “_” with “/”
        $b64 = strtr($data, '-_', '+/');

        // Decode Base64 string and return the original data
        return base64_decode($b64, $strict);
    }

    /**
     * Generate a string based on a mask partern
     */
    public static function generateStringFromPattern(string $pattern): string
    {
        $result = '';

        // Loop through the pattern and generate corresponding random characters
        for ($i = 0; $i < strlen($pattern); $i++) {
            $char = $pattern[$i];

            // Handle uppercase letters (A-Z)
            if ($char === 'A') {
                $result .= strtoupper(Str::random(1));
            }
            // Handle digits (0-9)
            elseif ($char === '0') {
                $result .= random_int(0, 9);
            }
            // Handle alphanumeric (A-Za-z0-9)
            elseif ($char === 'X') {
                $result .= Str::random(1);
            }
            // Handle underscore (_)
            elseif ($char === '_') {
                $result .= '_';
            }
            // Handle fixed hyphen (-) in the pattern
            elseif ($char === '-') {
                $result .= '-';
            }
        }

        return $result;
    }
}
