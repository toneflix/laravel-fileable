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
     * @param  array{url: string, ext: string, type: mixed, mime: mixed, size: int, path: string, isImage: mixed, dynamicLink: string, secureLink: string}  $fileInfo
     */
    public function __construct(
        public Model $model,
        public array $fileInfo,
    ) {}
}
