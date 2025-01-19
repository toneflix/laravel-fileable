<?php

namespace ToneflixCode\LaravelFileable\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileSaved
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     * 
     * @param Model $model   The current instance of the model that was saved.
     * @param array{url: string, ext: string, type: mixed, mime: mixed, size: int, path: string, isImage: mixed, dynamicLink: string, secureLink: string} $fileInfo     The file info array.
     * @param string $file_name     The original filename from the upload request file.
     */
    public function __construct(
        public Model $model,
        public array $fileInfo,
        public string $file_name,
    ) {}
}