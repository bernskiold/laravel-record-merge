<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Events\RecordMerged;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

it('can be constructed without a performer', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $source = clone $model;
    $target = clone $model;

    expect(new RecordMerged($source, $target))
        ->toBeInstanceOf(RecordMerged::class)
        ->source->toBe($source)
        ->target->toBe($target)
        ->performedBy->toBeNull();
});

it('can be constructed with a performer', function () {
    $model = new class extends Model implements Mergeable {
        use SupportsMerging;
    };

    $source = clone $model;
    $target = clone $model;

    $user = new class extends User {
    };

    expect(new RecordMerged($source, $target, $user))
        ->toBeInstanceOf(RecordMerged::class)
        ->source->toBe($source)
        ->target->toBe($target)
        ->performedBy->toBe($user);
});
