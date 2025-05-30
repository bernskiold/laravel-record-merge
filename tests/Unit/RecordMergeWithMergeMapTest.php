<?php

use Bernskiold\LaravelRecordMerge\Concerns\SupportsMerging;
use Bernskiold\LaravelRecordMerge\Contracts\Mergeable;
use Bernskiold\LaravelRecordMerge\Data\MergeMapConfig;
use Bernskiold\LaravelRecordMerge\RecordMerge;
use Illuminate\Database\Eloquent\Model;

describe('merge map configuration', function () {
    beforeEach(function () {
        $this->source = new class extends Model implements Mergeable {
            use SupportsMerging;

            protected $fillable = ['name', 'email', 'phone', 'address'];

            public function getAttributes()
            {
                return [
                    'id' => 1,
                    'name' => 'Source Name',
                    'email' => 'source@example.com',
                    'phone' => '123456789',
                    'address' => 'Source Address',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            public function getAttribute($key)
            {
                return $this->getAttributes()[$key] ?? null;
            }
        };

        $this->target = new class extends Model implements Mergeable {
            use SupportsMerging;

            protected $fillable = ['name', 'email', 'phone', 'address'];

            public function getAttributes()
            {
                return [
                    'id' => 2,
                    'name' => 'Target Name',
                    'email' => null,
                    'phone' => '987654321',
                    'address' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            public function getAttribute($key)
            {
                return $this->getAttributes()[$key] ?? null;
            }

            public function save()
            {
                return true;
            }
        };
    });

    it('can set a merge map', function () {
        $mergeMap = new MergeMapConfig([
            'name' => MergeMapConfig::SOURCE,
            'email' => MergeMapConfig::TARGET,
            'phone' => MergeMapConfig::SKIP,
        ]);

        $recordMerge = RecordMerge::new($this->source, $this->target)
            ->withMergeMap($mergeMap);

        expect($recordMerge->mergeMap)->toBe($mergeMap);
    });

    it('falls back to default behavior when merge map is empty', function () {
        // Mock the target to track attribute changes
        $target = Mockery::mock($this->target)->makePartial();
        $target->shouldReceive('save')->once()->andReturn(true);

        // Only email should be merged (as it's null in target)
        $target->shouldReceive('__set')->with('email', 'source@example.com')->once();
        // Address should be merged as it's null in target
        $target->shouldReceive('__set')->with('address', 'Source Address')->once();
        // Name and phone should not be merged as they're already set in target

        $recordMerge = RecordMerge::new($this->source, $target);
        $recordMerge->mergeAttributes();

        // No assertions needed as the mock expectations handle verification
    });

    it('merges attributes from source to target when specified in merge map', function () {
        // Mock the target to track attribute changes
        $target = Mockery::mock($this->target)->makePartial();
        $target->shouldReceive('save')->once()->andReturn(true);

        // Name should be merged from source even though it's set in target
        $target->shouldReceive('__set')->with('name', 'Source Name')->once();
        // Email should be merged as it's null in target
        $target->shouldReceive('__set')->with('email', 'source@example.com')->once();
        // Address should be merged as it's null in target
        $target->shouldReceive('__set')->with('address', 'Source Address')->once();
        // Phone should not be merged as it's set to be skipped

        $mergeMap = new MergeMapConfig([
            'name' => MergeMapConfig::SOURCE,
            'phone' => MergeMapConfig::SKIP,
        ]);

        $recordMerge = RecordMerge::new($this->source, $target)
            ->withMergeMap($mergeMap);

        $recordMerge->mergeAttributes();

        // No assertions needed as the mock expectations handle verification
    });

    it('keeps attributes on target when specified in merge map', function () {
        // Mock the target to track attribute changes
        $target = Mockery::mock($this->target)->makePartial();
        $target->shouldReceive('save')->once()->andReturn(true);

        // Email should be merged as it's null in target
        $target->shouldReceive('__set')->with('email', 'source@example.com')->once();
        // Address should be merged as it's null in target
        $target->shouldReceive('__set')->with('address', 'Source Address')->once();
        // Name should not be merged as it's set to be kept on target
        // Phone should not be merged as it's already set in target

        $mergeMap = new MergeMapConfig([
            'name' => MergeMapConfig::TARGET,
        ]);

        $recordMerge = RecordMerge::new($this->source, $target)
            ->withMergeMap($mergeMap);

        $recordMerge->mergeAttributes();

        // No assertions needed as the mock expectations handle verification
    });

    it('skips attributes when specified in merge map', function () {
        // Mock the target to track attribute changes
        $target = Mockery::mock($this->target)->makePartial();
        $target->shouldReceive('save')->once()->andReturn(true);

        // Email should be merged as it's null in target
        $target->shouldReceive('__set')->with('email', 'source@example.com')->once();
        // Address should not be merged as it's set to be skipped
        // Name should not be merged as it's already set in target
        // Phone should not be merged as it's already set in target

        $mergeMap = new MergeMapConfig([
            'address' => MergeMapConfig::SKIP,
        ]);

        $recordMerge = RecordMerge::new($this->source, $target)
            ->withMergeMap($mergeMap);

        $recordMerge->mergeAttributes();

        // No assertions needed as the mock expectations handle verification
    });
});

