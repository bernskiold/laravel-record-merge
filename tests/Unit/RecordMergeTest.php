<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Exceptions\InvalidRecordMergeException;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Illuminate\Database\Eloquent\Model;

describe('instantiation', function () {
    it('can be instantiated using the constructor without parameters', function () {
        expect(new RecordMerge())->toBeInstanceOf(RecordMerge::class);
    });

    it('can be instantiated with a source', function () {
        $source = mock(Bernskiold\LaravelRecordMerge\Contracts\Mergeable::class);
        $recordMerge = new RecordMerge($source);

        expect($recordMerge->source)->toBe($source)
            ->and($recordMerge->target)->toBeNull()
            ->and($recordMerge->performedBy)->toBeNull();
    });

    it('can be instantiated with target', function () {
        $target = mock(Bernskiold\LaravelRecordMerge\Contracts\Mergeable::class);
        $recordMerge = new RecordMerge(null, $target);

        expect($recordMerge->source)->toBeNull()
            ->and($recordMerge->target)->toBe($target)
            ->and($recordMerge->performedBy)->toBeNull();
    });

    it('can be instantiated with performer', function () {
        $performer = mock(Illuminate\Foundation\Auth\User::class);
        $recordMerge = new RecordMerge(null, null, $performer);

        expect($recordMerge->source)->toBeNull()
            ->and($recordMerge->target)->toBeNull()
            ->and($recordMerge->performedBy)->toBe($performer);
    });

    it('can be instantiated using the static new method', function () {
        $source = mock(Bernskiold\LaravelRecordMerge\Contracts\Mergeable::class);
        $target = mock(Bernskiold\LaravelRecordMerge\Contracts\Mergeable::class);
        $performer = mock(Illuminate\Foundation\Auth\User::class);

        $recordMerge = RecordMerge::new($source, $target, $performer);

        expect($recordMerge->source)->toBe($source)
            ->and($recordMerge->target)->toBe($target)
            ->and($recordMerge->performedBy)->toBe($performer);
    });
});

describe('validation', function () {

    it('throws validation exception if source is missing', function () {
        $target = mock(Bernskiold\LaravelRecordMerge\Contracts\Mergeable::class);
        $recordMerge = new RecordMerge(null, $target);

        $recordMerge->validate();
    })->throws(InvalidRecordMergeException::class, 'No source model was provided for merging from.');

    it('throws validation exception if target is missing', function () {
        $source = mock(Bernskiold\LaravelRecordMerge\Contracts\Mergeable::class);
        $recordMerge = new RecordMerge($source, null);

        $recordMerge->validate();
    })->throws(InvalidRecordMergeException::class, 'No target model was provided for merging into.');

    it('throws validation exception if source and target are not the same model', function () {
        $source = new class extends Model implements Mergeable {
            use SupportsMerging;
        };

        $target = new class extends Model implements Mergeable {
            use SupportsMerging;
        };

        $recordMerge = new RecordMerge($source, $target);

        $recordMerge->validate();
    })->throws(InvalidRecordMergeException::class);

    it('throws validation exception if source and target are the same model', function () {
        $source = new class extends Model implements Mergeable {
            use SupportsMerging;
        };

        $source->id = 1;

        $target = clone $source;
        $target->id = 1;

        $recordMerge = new RecordMerge($source, $target);

        $recordMerge->validate();
    })->throws(InvalidRecordMergeException::class, 'The source model and the target model are the same.');

});

describe('attribute merge checking', function () {

    beforeEach(function () {
        $this->source = new class extends Model implements Mergeable {
            use SupportsMerging;
        };

        $this->target = new class extends Model implements Mergeable {
            use SupportsMerging;
        };
    });

    test('if a list of mergeable attributes is provided and the attribute is in it, allow', function () {
        $allowed = RecordMerge::new($this->source, $this->target)
            ->allowAttributes('name')
            ->canAttributeBeMerged('name');

        expect($allowed)->toBeTrue();
    });

    test('if a list of mergeable attributes is provided and the attribute is not in it, deny', function () {
        $allowed = RecordMerge::new($this->source, $this->target)
            ->allowAttributes('name')
            ->canAttributeBeMerged('email');

        expect($allowed)->toBeFalse();
    });

    test('if no list of mergeable attributes is provided, allow all', function () {
        $allowed = RecordMerge::new($this->source, $this->target)
            ->canAttributeBeMerged('name');

        expect($allowed)->toBeTrue();
    });

    test('the primary key attribute cannot be merged', function () {
        $allowed = RecordMerge::new($this->source, $this->target)
            ->canAttributeBeMerged('id');

        expect($allowed)->toBeFalse();
    });

    test('attributes denied on the model cannot be merged', function () {
        $target = new class extends Model implements Mergeable {
            use SupportsMerging;

            public function getNotMergeableAttributes(): array
            {
                return ['email'];
            }
        };

        $allowed = RecordMerge::new($this->source, $target)
            ->canAttributeBeMerged('email');

        expect($allowed)->toBeFalse();
    });
});

describe('fluent configuration', function () {

    it('can set the source model', function () {
        $source = mock(Mergeable::class);
        $recordMerge = RecordMerge::new()->from($source);

        expect($recordMerge->source)->toBe($source);
    });

    it('can set the target model', function () {
        $target = mock(Mergeable::class);
        $recordMerge = RecordMerge::new()->to($target);

        expect($recordMerge->target)->toBe($target);
    });

    it('can set the performer', function () {
        $performer = mock(Illuminate\Foundation\Auth\User::class);
        $recordMerge = RecordMerge::new()->performedBy($performer);

        expect($recordMerge->performedBy)->toBe($performer);
    });

    it('can set after merging callback', function () {
        $callback = function () {
            return 'done';
        };

        $recordMerge = RecordMerge::new()->afterMerging($callback);

        expect($recordMerge->afterMergingCallback)->toBe($callback);
    });

    it('can allow deletion after merging', function () {
        $recordMerge = RecordMerge::new()->deleteAfterMerging();

        expect($recordMerge->deleteSourceAfterMerging)->toBeTrue();
    });

    it('can disallow deletion after merging', function () {
        $recordMerge = RecordMerge::new()->dontDeleteAfterMerging();

        expect($recordMerge->deleteSourceAfterMerging)->toBeFalse();
    });

    it('can set allowed attributes', function () {
        $recordMerge = RecordMerge::new()->allowedAttributes(['name', 'email']);

        expect($recordMerge->mergeableAttributes)->toBe(['name', 'email']);
    });

    it('can set singular allowed attributes', function () {
        $recordMerge = RecordMerge::new()->allowAttributes('name');

        expect($recordMerge->mergeableAttributes)->toBe(['name']);
    });

    it('can merge allowed attributes', function () {
        $recordMerge = RecordMerge::new()
            ->allowAttributes('name')
            ->allowAttributes('email')
            ->allowAttributes('name');

        expect($recordMerge->mergeableAttributes)->toBe([
            'name',
            'email',
        ]);
    });
});
