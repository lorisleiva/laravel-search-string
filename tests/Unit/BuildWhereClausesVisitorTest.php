<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\Concerns\DumpsWhereClauses;
use Lorisleiva\LaravelSearchString\Tests\Concerns\GeneratesEloquentBuilder;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\BuildWhereClausesVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class ResolveQueryWhereClauseTest extends TestCase
{
    use DumpsWhereClauses;
    use GeneratesEloquentBuilder;

    public function visitors($builder, $manager)
    {
        return [
            new RemoveNotSymbolVisitor,
            new BuildWhereClausesVisitor($builder, $manager),
        ];
    }

    /** @test */
    function it_generates_basic_where_clauses_that_match_the_query_operator()
    {
        $this->assertWhereClauses('foo:1', ['Basic[and]' => 'foo = 1']);
        $this->assertWhereClauses('foo=1', ['Basic[and]' => 'foo = 1']);
        $this->assertWhereClauses('foo=Hello', ['Basic[and]' => 'foo = Hello']);
        $this->assertWhereClauses('foo="Hello World"', ['Basic[and]' => 'foo = Hello World']);
        $this->assertWhereClauses('foo:1,2,3', ['Basic[and]' => 'foo = [1, 2, 3]']);
        $this->assertWhereClauses('not foo:1', ['Basic[and]' => 'foo != 1']);
        $this->assertWhereClauses('not foo=Hello', ['Basic[and]' => 'foo != Hello']);
        $this->assertWhereClauses('not foo:1,2,3', ['Basic[and]' => 'foo != [1, 2, 3]']);

        $this->assertWhereClauses('foo<0', ['Basic[and]' => 'foo < 0']);
        $this->assertWhereClauses('foo<=0', ['Basic[and]' => 'foo <= 0']);
        $this->assertWhereClauses('foo>0', ['Basic[and]' => 'foo > 0']);
        $this->assertWhereClauses('foo>=0', ['Basic[and]' => 'foo >= 0']);
        $this->assertWhereClauses('not foo<0', ['Basic[and]' => 'foo >= 0']);
        $this->assertWhereClauses('not foo<=0', ['Basic[and]' => 'foo > 0']);
        $this->assertWhereClauses('not foo>0', ['Basic[and]' => 'foo <= 0']);
        $this->assertWhereClauses('not foo>=0', ['Basic[and]' => 'foo < 0']);

        // boolean_variable is defined in the `columns.boolean` option.
        $this->assertWhereClauses('boolean_variable', ['Basic[and]' => 'boolean_variable = true']);
        $this->assertWhereClauses('not boolean_variable', ['Basic[and]' => 'boolean_variable = false']);
    }

    /** @test */
    function it_can_generate_in_and_not_in_where_clauses()
    {
        $this->assertWhereClauses('foo in (1,2,3)', ['In[and]' => 'foo [1, 2, 3]']);
        $this->assertWhereClauses('not foo in (1,2,3)', ['NotIn[and]' => 'foo [1, 2, 3]']);
    }

    /** @test */
    function it_searches_using_like_where_clauses()
    {
        $this->assertWhereClauses('foobar', [
            'Nested[and]' => [
                'Basic[or]' => 'description like %foobar%'
            ]
        ]);
    }

    // TODO: More tests/features:
    // - If value is "null", use $builder->whereNull();
    // - If value is not "null", use $builder->whereNotNull();
    // - If value is numeric, parse from string (important for sql?)
    // - If column is date column, use Carbon to parse the value.
    // - Throw error if column does't exists (from config?)
    // - Use mapping to allow different names than database columns (but default to database columns).
}