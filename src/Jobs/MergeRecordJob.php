<?php

namespace Bernskiold\LaravelRecordMerge\Jobs;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeConfig;
use Bernskiold\LaravelRecordMerge\Events\RecordMerged;
use Bernskiold\LaravelRecordMerge\Events\RecordMergeFailed;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MergeRecordJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Mergeable $source,
        public Mergeable $target,
        public ?Authenticatable $performedBy = null,
        public ?MergeConfig $mergeConfig = null,
    ) {
        $this->onConnection(config('record-merge.queue.connection', null));
        $this->onQueue(config('record-merge.queue.queue'));
    }

    public function handle(): void
    {
        RecordMerge::new($this->source, $this->target)
            ->withMergeConfig($this->mergeConfig)
            ->performedBy($this->performedBy)
            ->afterMerging(function (Mergeable $source, Mergeable $target) {
                // Dispatch an event after merging the records.
                event(new RecordMerged($source, $target, $this->performedBy));
            })
            ->merge();
    }

    public function uniqueId(): string
    {
        return str($this->source->getMorphClass())
            ->append(':', $this->source->getKey())
            ->append('-', $this->target->getMorphClass())
            ->append(':', $this->target->getKey())
            ->toString();
    }

    public function fail($exception = null): void
    {
        event(new RecordMergeFailed($this->source, $this->target, $this->performedBy, $exception));
    }

    public function tags(): array
    {
        return [
            'record-merge',
            'source:'.$this->source->getMorphClass().':'.$this->source->getKey(),
            'target:'.$this->target->getMorphClass().':'.$this->target->getKey(),
        ];
    }
}
