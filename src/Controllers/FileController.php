<?php

namespace ToneflixCode\LaravelFileable\Controllers;

use Illuminate\Routing\Controller;
use Raju\Streamer\Helpers\VideoStream;
use ToneflixCode\LaravelFileable\Facades\Media;

class FileController extends Controller
{
    public function show(string $filePath)
    {
        return Media::dynamicFile($filePath);
    }

    public function showResponsive(string $size, string $filePath)
    {
        return Media::resizeResponse($filePath, $size);
    }
}
