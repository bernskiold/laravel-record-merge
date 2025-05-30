<?php

use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeConfig;
use Bernskiold\LaravelRecordMerge\Enums\MergeStrategy;
use Bernskiold\LaravelRecordMerge\Events\RecordMerged;
use Bernskiold\LaravelRecordMerge\Events\RecordMergeFailed;
use Bernskiold\LaravelRecordMerge\Jobs\MergeRecordJob;
use Bernskiold\LaravelRecordMerge\Tests\Models\ModelWithoutRelationships;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;

it('can be constructed without performer', function () {
    $source = mock(Mergeable::class);
    $target = mock(Mergeable::class);

    $job = new MergeRecordJob($source, $target);

    expect($job->source)->toBe($source)
        ->and($job->target)->toBe($target)
        ->and($job->performedBy)->toBeNull()
        ->and($job->queue)->toBe(config('record-merge.queue.queue'))
        ->and($job->connection)->toBe(config('record-merge.queue.connection', null));
});

it('can be constructed with performer', function () {
    $source = mock(Mergeable::class);
    $target = mock(Mergeable::class);
    $performer = mock(User::class);

    $job = new MergeRecordJob($source, $target, $performer);

    expect($job->source)->toBe($source)
        ->and($job->target)->toBe($target)
        ->and($job->performedBy)->toBe($performer)
        ->and($job->queue)->toBe(config('record-merge.queue.queue'))
        ->and($job->connection)->toBe(config('record-merge.queue.connection', null));
});

it('can be constructed with merge map', function () {
    $source = mock(Mergeable::class);
    $target = mock(Mergeable::class);
    $mergeMap = new MergeConfig(['name' => MergeStrategy::UseSource]);

    $job = new MergeRecordJob($source, $target, null, $mergeMap);

    expect($job->source)->toBe($source)
        ->and($job->target)->toBe($target)
        ->and($job->performedBy)->toBeNull()
        ->and($job->mergeConfig)->toBe($mergeMap)
        ->and($job->queue)->toBe(config('record-merge.queue.queue'))
        ->and($job->connection)->toBe(config('record-merge.queue.connection', null));
});

it('can generate a unique ID', function () {
    $source = mock(Mergeable::class);
    $target = mock(Mergeable::class);

    $source->shouldReceive('getMorphClass')->andReturn('SourceModel');
    $source->shouldReceive('getKey')->andReturn(1);
    $target->shouldReceive('getMorphClass')->andReturn('TargetModel');
    $target->shouldReceive('getKey')->andReturn(2);

    $job = new MergeRecordJob($source, $target);

    expect($job->uniqueId())->toBe('SourceModel:1-TargetModel:2');
});

it('triggers RecordMergeFailed event on failure', function () {
    Event::fake();

    $source = mock(Mergeable::class);
    $target = mock(Mergeable::class);
    $performer = mock(User::class);

    $job = new MergeRecordJob($source, $target, $performer);
    $job->fail(new Exception('Test exception'));

    Event::assertDispatched(RecordMergeFailed::class, function ($event) use ($source, $target, $performer) {
        return $event->source === $source &&
            $event->target === $target &&
            $event->performedBy === $performer &&
            $event->exception instanceof Exception;
    });
});

it('can generate tags', function () {
    $source = mock(Mergeable::class);
    $target = mock(Mergeable::class);

    $source->shouldReceive('getMorphClass')->andReturn('SourceModel');
    $source->shouldReceive('getKey')->andReturn(1);
    $target->shouldReceive('getMorphClass')->andReturn('TargetModel');
    $target->shouldReceive('getKey')->andReturn(2);

    $job = new MergeRecordJob($source, $target);

    expect($job->tags())->toBe([
        'record-merge',
        'source:SourceModel:1',
        'target:TargetModel:2',
    ]);
});

it('performs the job', function () {
    Event::fake();

    $source = ModelWithoutRelationships::create(['name' => 'One']);
    $target = ModelWithoutRelationships::create(['name' => 'Two']);

    $performer = mock(User::class);

    $job = new MergeRecordJob($source, $target, $performer);

    $job->handle();

    Event::assertDispatched(RecordMerged::class, function ($event) use ($source, $target, $performer) {
        return $event->source === $source &&
            $event->target === $target &&
            $event->performedBy === $performer;
    });
});

it('performs the job with merge map', function () {
    Event::fake();

    $source = ModelWithoutRelationships::create(['name' => 'One']);
    $target = ModelWithoutRelationships::create(['name' => 'Two']);
    $mergeMap = new MergeConfig(['name' => MergeStrategy::UseSource]);

    $performer = mock(User::class);

    $job = new MergeRecordJob($source, $target, $performer, $mergeMap);

    $job->handle();

    Event::assertDispatched(RecordMerged::class, function ($event) use ($source, $target, $performer) {
        return $event->source === $source &&
            $event->target === $target &&
            $event->performedBy === $performer;
    });
});
