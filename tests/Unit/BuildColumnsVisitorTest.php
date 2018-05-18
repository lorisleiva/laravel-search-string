<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\Concerns\DumpsWhereClauses;
use Lorisleiva\LaravelSearchString\Tests\Concerns\GeneratesEloquentBuilder;
use Lorisleiva\LaravelSearchString\Tests\Stubs\DummyModelWithoutOptions;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\BuildColumnsVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class BuildColumnsVisitorTest extends TestCase
{
    use DumpsWhereClauses;
    use GeneratesEloquentBuilder;

    public function visitors($manager, $builder)
    {
        return [
            new RemoveNotSymbolVisitor,
            new BuildColumnsVisitor($manager, $builder),
        ];
    }

    /** @test */
    function it_generates_basic_where_clauses_that_match_the_query_operator()
    {
        $this->assertWhereClauses('name:1', ['Basic[and][0]' => 'name = 1']);
        $this->assertWhereClauses('name=1', ['Basic[and][0]' => 'name = 1']);
        $this->assertWhereClauses('name=Hello', ['Basic[and][0]' => 'name = Hello']);
        $this->assertWhereClauses('name="Hello World"', ['Basic[and][0]' => 'name = Hello World']);
        $this->assertWhereClauses('name:1,2,3', ['Basic[and][0]' => 'name = [1, 2, 3]']);
        $this->assertWhereClauses('not name:1', ['Basic[and][0]' => 'name != 1']);
        $this->assertWhereClauses('not name=Hello', ['Basic[and][0]' => 'name != Hello']);
        $this->assertWhereClauses('not name:1,2,3', ['Basic[and][0]' => 'name != [1, 2, 3]']);

        $this->assertWhereClauses('name<0', ['Basic[and][0]' => 'name < 0']);
        $this->assertWhereClauses('name<=0', ['Basic[and][0]' => 'name <= 0']);
        $this->assertWhereClauses('name>0', ['Basic[and][0]' => 'name > 0']);
        $this->assertWhereClauses('name>=0', ['Basic[and][0]' => 'name >= 0']);
        $this->assertWhereClauses('not name<0', ['Basic[and][0]' => 'name >= 0']);
        $this->assertWhereClauses('not name<=0', ['Basic[and][0]' => 'name > 0']);
        $this->assertWhereClauses('not name>0', ['Basic[and][0]' => 'name <= 0']);
        $this->assertWhereClauses('not name>=0', ['Basic[and][0]' => 'name < 0']);

        // boolean_variable is defined in the `columns.boolean` option.
        $this->assertWhereClauses('boolean_variable', ['Basic[and][0]' => 'boolean_variable = true']);
        $this->assertWhereClauses('not boolean_variable', ['Basic[and][0]' => 'boolean_variable = false']);
    }

    /** @test */
    function it_can_generate_in_and_not_in_where_clauses()
    {
        $this->assertWhereClauses('name in (1,2,3)', ['In[and][0]' => 'name [1, 2, 3]']);
        $this->assertWhereClauses('not name in (1,2,3)', ['NotIn[and][0]' => 'name [1, 2, 3]']);
    }

    /** @test */
    function it_searches_using_like_where_clauses()
    {
        $this->assertWhereClauses('foobar', [
            'Nested[and][0]' => [
                'Basic[or][0]' => 'name like %foobar%',
                'Basic[or][1]' => 'description like %foobar%',
            ]
        ]);

        $this->assertWhereClauses('not foobar', [
            'Nested[and][0]' => [
                'Basic[and][0]' => 'name not like %foobar%',
                'Basic[and][1]' => 'description not like %foobar%',
            ]
        ]);
    }

    /** @test */
    function it_does_not_add_where_clause_if_not_searchable_columns_were_given()
    {
        $model = new DummyModelWithoutOptions;

        $this->assertWhereClauses('foobar', [], $model);
        $this->assertWhereClauses('not foobar', [], $model);
    }

    /** @test */
    function it_does_not_nest_where_clauses_if_only_one_searchable_columns_is_given()
    {
        $model = new class extends DummyModelWithoutOptions {
            public $searchStringColumns = [
                'name' => [ 'searchable' => true ]
            ];
        };

        $this->assertWhereClauses('foobar', [
            'Basic[and][0]' => 'name like %foobar%'
        ], $model);

        $this->assertWhereClauses('not foobar', [
            'Basic[and][0]' => 'name not like %foobar%'
        ], $model);
    }

    /** @test */
    function it_wraps_basic_queries_in_nested_and_or_where_clauses()
    {
        $this->assertWhereClauses('name:1 and date>1', [
            'Nested[and][0]' => [
                'Basic[and][0]' => 'name = 1',
                'Basic[and][1]' => 'date > 1',
            ]
        ]);

        $this->assertWhereClauses('name:1 or date>1', [
            'Nested[and][0]' => [
                'Basic[or][0]' => 'name = 1',
                'Basic[or][1]' => 'date > 1',
            ]
        ]);
    }

    /** @test */
    function it_wraps_search_queries_in_nested_and_or_where_clauses()
    {
        $this->assertWhereClauses('foo and bar', [
            'Nested[and][0]' => [
                'Nested[and][0]' => [
                    'Basic[or][0]' => 'name like %foo%',
                    'Basic[or][1]' => 'description like %foo%',
                ],
                'Nested[and][1]' => [
                    'Basic[or][0]' => 'name like %bar%',
                    'Basic[or][1]' => 'description like %bar%',
                ],
            ]
        ]);

        $this->assertWhereClauses('foo or bar', [
            'Nested[and][0]' => [
                'Nested[or][0]' => [
                    'Basic[or][0]' => 'name like %foo%',
                    'Basic[or][1]' => 'description like %foo%',
                ],
                'Nested[or][1]' => [
                    'Basic[or][0]' => 'name like %bar%',
                    'Basic[or][1]' => 'description like %bar%',
                ],
            ]
        ]);

        $this->assertWhereClauses('not foo or not bar', [
            'Nested[and][0]' => [
                'Nested[or][0]' => [
                    'Basic[and][0]' => 'name not like %foo%',
                    'Basic[and][1]' => 'description not like %foo%',
                ],
                'Nested[or][1]' => [
                    'Basic[and][0]' => 'name not like %bar%',
                    'Basic[and][1]' => 'description not like %bar%',
                ],
            ]
        ]);
    }

    /** @test */
    function it_wraps_complex_and_or_operators_in_nested_where_clauses()
    {
        $this->assertWhereClauses('name:4 or (name:1 or name:2) and date>1 or name:3', [
            'Nested[and][0]' => [
                'Basic[or][0]' => 'name = 4',
                'Nested[or][1]' => [
                    'Nested[and][0]' => [
                        'Basic[or][0]' => 'name = 1',
                        'Basic[or][1]' => 'name = 2',
                    ],
                    'Basic[and][1]' => 'date > 1',
                ],
                'Basic[or][2]' => 'name = 3',
            ]
        ]);
    }

    // TODO: More tests/features:
    // - If value is "null", use $builder->whereNull();
    // - If value is not "null", use $builder->whereNotNull();
    // - If value is numeric, parse from string (important for sql?)
    // - Throw error if column does't exists
}