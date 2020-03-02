<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyChild;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModel;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class DatabaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->withFactories(__DIR__ . '/../database/factories');
    }

    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('database.default', 'testing');
    }

    public function relationQueriesDataProvider()
    {
        return [
            'Basic has hasMany relation' => [
                'has(comments)',
                function() {
                    return factory(DummyModel::class, 2)->create()->each(function ($post) {
                        $post->comments()->saveMany(factory(DummyChild::class, 1)->make());
                    });
                },
                function() {
                    return factory(DummyModel::class, 3)->create();
                }
            ],
            'Count hasMany relation greaterThan' => [
                'has(comments) > 2',
                function() {
                    return factory(DummyModel::class, 2)->create()->each(function ($post) {
                        $post->comments()->saveMany(factory(DummyChild::class, 3)->make());
                    });
                },
                function() {
                    return factory(DummyModel::class, 3)->create()->each(function ($post) {
                        $post->comments()->saveMany(factory(DummyChild::class, 1)->make());
                    });
                }
            ],
            'Count hasMany relation lessThan' => [
                'has(comments) < 2',
                function() {
                    return factory(DummyModel::class, 2)->create()->each(function ($post) {
                        $post->comments()->saveMany(factory(DummyChild::class, 1)->make());
                    });
                },
                function() {
                    return factory(DummyModel::class, 3)->create()->each(function ($post) {
                        $post->comments()->saveMany(factory(DummyChild::class, 3)->make());
                    });
                }
            ],
        ];
    }

    /**
     * @test
     * @dataProvider relationQueriesDataProvider
     */
    public function it_filters_by_relation($input, \Closure $matches, \Closure $notMatches)
    {
        $has = $matches()->map->id;
        $missing = $notMatches()->map->id;

        // Basic search string

        $includedResults = DummyModel::usingSearchString($input)->pluck('id')->toArray();

        $count = count($has);

        $this->assertCount($count, $includedResults, "Failed asserting that the search string includes $count records");

        foreach ($has as $id) {
            $this->assertContains($id, $includedResults, "Failed asserting that record ID $id meets the search string");
        }

        foreach ($missing as $id) {
            $this->assertNotContains($id, $includedResults, "Failed asserting that record ID $id does not meet the search string");
        }

        // Negated search string

        $excludedResults = DummyModel::usingSearchString("not ($input)")->pluck('id')->toArray();

        $count = count($missing);

        $this->assertCount($count, $excludedResults, "Failed asserting that the search string excludes $count records");

        foreach ($missing as $id) {
            $this->assertContains($id, $excludedResults, "Failed asserting that record ID $id does not meet the search string");
        }

        foreach ($has as $id) {
            $this->assertNotContains($id, $excludedResults, "Failed asserting that record ID $id meets the search string");
        }

    }
}