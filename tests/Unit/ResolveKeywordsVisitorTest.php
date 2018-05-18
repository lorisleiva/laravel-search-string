<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;
use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\Concerns\GeneratesEloquentBuilder;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;
use Lorisleiva\LaravelSearchString\Visitor\ResolveKeywordsVisitor;

class ResolveKeywordsVisitorTest extends TestCase
{
    use GeneratesEloquentBuilder;

    public function visitors($builder, $manager)
    {
        return [
            new RemoveNotSymbolVisitor(),
            new ResolveKeywordsVisitor($builder, $manager),
        ];
    }

    /**
     * Select
     */

    /** @test */
    function it_sets_the_columns_of_the_builder()
    {
        $builder = $this->getBuilderFor('fields:name');
        $this->assertEquals(['name'], $builder->getQuery()->columns);
    }

    /** @test */
    function it_excludes_columns_when_operator_is_negative()
    {
        $builder = $this->getBuilderFor('not fields:name');

        $this->assertEquals(
            ['price', 'description', 'paid', 'boolean_variable', 'created_at'], 
            $builder->getQuery()->columns
        );
    }

    /** @test */
    function it_can_set_and_exclude_multiple_columns()
    {
        $builder = $this->getBuilderFor('fields:name,price,description');
        $this->assertEquals(['name', 'price', 'description'], $builder->getQuery()->columns);

        $builder = $this->getBuilderFor('not fields:name,price,description');
        $this->assertEquals(['paid', 'boolean_variable', 'created_at'], $builder->getQuery()->columns);
    }

    /** @test */
    function it_uses_only_the_last_select_that_matches()
    {
        $builder = $this->getBuilderFor('fields:name fields:price fields:description');
        $this->assertEquals(['description'], $builder->getQuery()->columns);
    }

    /**
     * OrderBy
     */

    /** @test */
    function it_sets_the_order_by_of_the_builder()
    {
        $builder = $this->getBuilderFor('sort:name');

        $this->assertEquals([
            [ 'column' => 'name', 'direction' => 'asc' ],
        ], $builder->getQuery()->orders);
    }

    /** @test */
    function it_sets_the_descending_order_when_preceded_by_a_minus()
    {
        $builder = $this->getBuilderFor('sort:-name');

        $this->assertEquals([
            [ 'column' => 'name', 'direction' => 'desc' ],
        ], $builder->getQuery()->orders);
    }

    /** @test */
    function it_can_set_multiple_order_by()
    {
        $builder = $this->getBuilderFor('sort:name,-price,created_at');

        $this->assertEquals([
            [ 'column' => 'name', 'direction' => 'asc' ],
            [ 'column' => 'price', 'direction' => 'desc' ],
            [ 'column' => 'created_at', 'direction' => 'asc' ],
        ], $builder->getQuery()->orders);
    }

    /** @test */
    function it_uses_only_the_last_order_by_that_matches()
    {
        $builder = $this->getBuilderFor('sort:name sort:-price sort:created_at');

        $this->assertEquals([
            [ 'column' => 'created_at', 'direction' => 'asc' ],
        ], $builder->getQuery()->orders);
    }

    /**
     * Limit
     */

    /** @test */
    function it_sets_the_limit_of_the_builder()
    {
        $builder = $this->getBuilderFor('limit:10');
        $this->assertEquals(10, $builder->getQuery()->limit);
    }

    /** @test */
    function it_throws_an_exception_if_the_limit_is_not_an_integer()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('limit:foobar');
    }

    /** @test */
    function it_throws_an_exception_if_the_limit_is_an_array()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('limit:10,foo,23');
    }

    /** @test */
    function it_uses_only_the_last_limit_that_matches()
    {
        $builder = $this->getBuilderFor('limit:10 limit:20 limit:30');
        $this->assertEquals(30, $builder->getQuery()->limit);
    }

    /**
     * Offset
     */
    
    /** @test */
    function it_sets_the_offset_of_the_builder()
    {
        $builder = $this->getBuilderFor('from:10');
        $this->assertEquals(10, $builder->getQuery()->offset);
    }

    /** @test */
    function it_throws_an_exception_if_the_offset_is_not_an_integer()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('from:foobar');
    }

    /** @test */
    function it_throws_an_exception_if_the_offset_is_an_array()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('from:10,foo,23');
    }

    /** @test */
    function it_uses_only_the_last_offset_that_matches()
    {
        $builder = $this->getBuilderFor('from:10 from:20 from:30');
        $this->assertEquals(30, $builder->getQuery()->offset);
    }

    /**
     * Generic
     */
    
    /** @test */
    function it_does_not_change_the_ast()
    {
        $ast = $this->resolveKeywordWithRule('foo:1 bar:2 faz:3', '/^f/');
        $this->assertAstEquals('AND(QUERY(foo = 1), QUERY(bar = 2), QUERY(faz = 3))', $ast);
    }

    /** @test */
    function it_call_the_use_resolve_keyword_method_for_every_match()
    {
        $matches = collect();
        $callback = function ($query) use ($matches) {
            $matches->push($query->accept(new InlineDumpVisitor));
        };

        $ast = $this->resolveKeywordWithRule('foo:1 bar:2 faz:3', '/^f/', $callback);
        $this->assertEquals(['QUERY(foo = 1)', 'QUERY(faz = 3)'], $matches->toArray());
    }

    /** @test */
    function it_keeps_track_of_the_last_query_that_matched()
    {
        $matches = collect();
        $callback = function ($query, $lastQuery) use ($matches) {
            $this->assertEquals($matches->last(), $lastQuery);
            $matches->push($query);
        };

        $this->resolveKeywordWithRule('foo:1 bar:2 faz:3', '/^f/', $callback);
    }

    public function assertAstEquals($expectedAst, $ast)
    {
        $this->assertEquals($expectedAst, $ast->accept(new InlineDumpVisitor));
    }

    public function resolveKeywordWithRule($input, $rule, $callback = null)
    {
        $manager = $this->getSearchStringManager(new class($rule) extends Model {
            use SearchString;

            public function __construct($rule)
            {
                $this->searchStringKeywords = ['banana_keyword' => $rule];
            }
        });

        return $this->parse($input)->accept(
            new class($manager, $callback) extends ResolveKeywordsVisitor {
                protected $callback;

                public function __construct($manager, $callback)
                {
                    $this->callback = $callback ?? function () {};
                    parent::__construct(null, $manager);
                }

                public function resolveKeyword($keyword, $query, $lastQuery)
                {
                    ($this->callback)($query, $lastQuery);
                }
            }
        );
    }
}