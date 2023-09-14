<?php

namespace ToneflixCode\LaravelFileable\Intervention;

use Intervention\Image\Filters\FilterInterface;
use Intervention\Image\Image;

class Media1080Square implements FilterInterface
{
    public function applyFilter(Image $image)
    {
        return $image->fit(1080, 1080);
    }
}