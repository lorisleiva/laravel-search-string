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
            'Basic filter' => [
                'name : "TESTABLE NAME"',
                DummyModel::class,
                [
                    'count' => 2,
                    'attributes' => ['name' => 'TESTABLE NAME'],
                ],
                [
                    'count' => 3,
                ]
            ],
            'Basic has hasMany relation' => [
                'has(comments)',
                DummyModel::class,
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'count' => 1,
                        ],
                    ],
                ],
                [
                    'count' => 3,
                ]
            ],
            'Count hasMany relation greaterThan' => [
                'has(comments) > 2',
                DummyModel::class,
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'count' => 3,
                        ],
                    ],
                ],
                [
                    'count' => 3,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'count' => 1,
                        ],
                    ],
                ]
            ],
            'Count hasMany relation lessThan' => [
                'has(comments) < 2',
                DummyModel::class,
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'count' => 1,
                        ],
                    ],
                ],
                [
                    'count' => 3,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'count' => 3,
                        ],
                    ],
                ]
            ],
            'Has hasMany relation with constraints' => [
                'has(comments { active })',
                DummyModel::class,
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true],
                            'count' => 3,
                        ],
                    ],
                ],
                [
                    'count' => 3,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => false],
                            'count' => 2,
                        ],
                    ],
                ]
            ],
            'Count has hasMany relation with constraints greaterThan' => [
                'has(comments { active }) > 2',
                DummyModel::class,
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true],
                            'count' => 3,
                        ],
                    ],
                ],
                [
                    'count' => 3,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true],
                            'count' => 1,
                        ],
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => false],
                            'count' => 2,
                        ],
                    ],
                ]
            ],
            'Count has hasMany relation with constraints lessThan' => [
                'has(comments { active }) < 2',
                DummyModel::class,
                [
                    'count' => 3,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true],
                            'count' => 1,
                        ],
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => false],
                            'count' => 2,
                        ],
                    ],
                ],
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true],
                            'count' => 3,
                        ],
                    ],
                ]
            ],
            'Dot-nested hasMany relation' => [
                'comments.active',
                DummyModel::class,
                [
                    'count' => 2,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true],
                            'count' => 3,
                        ],
                    ],
                ],
                [
                    'count' => 3,
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => false],
                            'count' => 2,
                        ],
                    ],
                ]
            ],
            'Multiple dot-nested hasMany relations treated as separate queries' => [
                'comments.active and comments.title = "Foo"',
                DummyModel::class,
                [
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true, 'title' => 'Foo'],
                        ],
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => true, 'title' => 'Bar'],
                        ],
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => false, 'title' => 'Foo'],
                        ],
                    ],
                ],
                [
                    'children' => [
                        [
                            'relation' => 'comments',
                            'model' => DummyChild::class,
                            'attributes' => ['active' => false, 'title' => 'Bar'],
                        ],
                    ],
                ],
                false
            ],
        ];
    }

    /**
     * @test
     * @dataProvider relationQueriesDataProvider
     */
    public function it_filters_by_relation(string $input, string $model, array $matches, array $notMatches, bool $testNegated = true)
    {
        $matches['model'] = $matches['model'] ?? $model;
        $notMatches['model'] = $notMatches['model'] ?? $model;

        $has = $this->makeModels($matches, true)->map->id;
        $missing = $this->makeModels($notMatches, true)->map->id;

        $includedResults = DummyModel::usingSearchString($input)->pluck('id')->toArray();

        $count = count($has);

        $this->assertCount($count, $includedResults, "Failed asserting that the search string includes $count records");

        foreach ($has as $id) {
            $this->assertContains($id, $includedResults, "Failed asserting that record ID $id meets the search string");
        }

        foreach ($missing as $id) {
            $this->assertNotContains($id, $includedResults, "Failed asserting that record ID $id does not meet the search string");
        }

        if (!$testNegated) {
            return;
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

    protected function makeModels(array $options, bool $create = false)
    {
        $method = $create ? 'create' : 'make';

        $records = factory($options['model'], $options['count'] ?? 1)->$method($options['attributes'] ?? []);

        // dump($records->toArray()); //FIXME

        if ($children = $options['children'] ?? false) {
            $records->each(function ($model) use ($children) {
                foreach ($children as $child) {
                    $relation = $child['relation'];
                    $model->$relation()->saveMany($this->makeModels($child));
                }
            });
        }

        return $records;
    }
}