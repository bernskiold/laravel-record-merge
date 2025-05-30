<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Illuminate\Database\Eloquent\Model;

test('no source exception', function () {
    $exception = Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException::noSource();
    expect($exception->getMessage())->toBe('No source model was provided for merging from.');
});

test('no target exception', function () {
    $exception = Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException::noTarget();
    expect($exception->getMessage())->toBe('No target model was provided for merging into.');
});

test('not same model exception', function () {
    $source = new class extends Model implements Mergeable
    {
        use SupportsMerging;
    };

    $target = new class extends Model implements Mergeable
    {
        use SupportsMerging;
    };

    $exception = Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException::notSameModel($source, $target);

    $sourceClass = get_class($source);
    $targetClass = get_class($target);

    expect($exception->getMessage())
        ->toBe("The source model [$sourceClass] and target model [$targetClass] are not the same.")
        ->and($exception->getSource())->toBe($source)
        ->and($exception->getTarget())->toBe($target);
});

test('same id exception', function () {
    $model = new class extends Model implements Mergeable
    {
        use SupportsMerging;

        public function getKey()
        {
            return 1; // Simulating a model with an ID of 1
        }
    };

    $exception = Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException::sameId($model, $model);

    expect($exception->getMessage())->toBe('The source model and the target model are the same.')
        ->and($exception->getSource())->toBe($model)
        ->and($exception->getTarget())->toBe($model);
});
