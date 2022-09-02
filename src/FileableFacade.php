<?php

namespace ToneflixCode\LaravelFileable;

use Illuminate\Support\Facades\Facade;

/**
 * @see \ToneflixCode\FileableFacade\Skeleton\SkeletonClass
 */
class FileableFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'fileable';
    }
}