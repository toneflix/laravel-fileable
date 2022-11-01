<?php

namespace ToneflixCode\LaravelFileable\Facades;

use Illuminate\Support\Facades\Facade;
use ToneflixCode\LaravelFileable\Media;

/**
 * @see \ToneflixCode\FileableFacade\Skeleton\SkeletonClass
 */
class Fileable extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return Media::class;
    }
}
