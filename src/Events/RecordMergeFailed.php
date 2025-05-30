<?php

namespace Bernskiold\LaravelRecordMerge\Events;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

class RecordMergeFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Mergeable $source,
        public Mergeable $target,
        public ?Authenticatable $performedBy = null,
        public ?Throwable $exception = null,
    ) {}
}
