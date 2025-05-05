<?php

namespace Bernskiold\LaravelRecordMerge\Jobs;

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use function method_exists;

class MergeRecordJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    public function __construct(
        protected Mergeable $source,
        protected Mergeable $target
    )
    {
        $this->onQueue(config('record-merge.queue.queue', 'default'));
    }

    public function handle(): void
    {
        RecordMerge::new($this->source, $this->target)
            ->afterMerging(function (Mergeable $source, Mergeable $target) {
                if (method_exists($this->source, 'updateAfterMerging')) {
                    $this->source::updateAfterMerging($source, $target);
                }
            })
            ->merge();
    }

    public function uniqueId(): string
    {
        return $this->source->getKey() . '-' . $this->target->getKey();
    }

    public function tags(): array
    {
        return [
            'record-merge',
            'source:' . $this->source->getMorphClass() . ':' . $this->source->getKey(),
            'target:' . $this->target->getMorphClass() . ':' . $this->target->getKey(),
        ];
    }
}