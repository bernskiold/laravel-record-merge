<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Events\RecordMergeFailed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

it('can be constructed without a performer and exception', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $source = clone $model;
    $target = clone $model;

    expect(new RecordMergeFailed($source, $target))
        ->toBeInstanceOf(RecordMergeFailed::class)
        ->source->toBe($source)
        ->target->toBe($target)
        ->performedBy->toBeNull()
        ->exception->toBeNull();
});

it('can be constructed with a performer', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $source = clone $model;
    $target = clone $model;

    $user = new class extends User {
    };

    expect(new RecordMergeFailed($source, $target, $user))
        ->toBeInstanceOf(RecordMergeFailed::class)
        ->source->toBe($source)
        ->target->toBe($target)
        ->performedBy->toBe($user)
        ->exception->toBeNull();
});

it('can be constructed with an exception', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $source = clone $model;
    $target = clone $model;

    $exception = new Exception('Test exception');

    expect(new RecordMergeFailed($source, $target, null, $exception))
        ->toBeInstanceOf(RecordMergeFailed::class)
        ->source->toBe($source)
        ->target->toBe($target)
        ->performedBy->toBeNull()
        ->exception->toBe($exception);
});
