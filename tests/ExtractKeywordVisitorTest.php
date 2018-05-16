<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Visitor\ExtractKeywordVisitor;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;

class ExtractKeywordVisitorTest extends TestCase
{
    /** @test */
    function it_transforms_extracted_queries_to_null_symbols()
    {
        $ast = $this->extractKeywordWithRule('foo:bar', '/^foo$/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('boolean_variable', '/^bool/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo:1', '/f/', '/=/', '/1/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo>40', '/f/', '/^>$/', '/\d+/');
        $this->assertAstEquals('NULL', $ast);
    }

    /** @test */
    function it_leaves_queries_that_do_not_match_intact()
    {
        $ast = $this->extractKeywordWithRule('foo:bar', '/^baz$/');
        $this->assertAstEquals('QUERY(foo = bar)', $ast);

        $ast = $this->extractKeywordWithRule('boolean_variable', '/^variable/');
        $this->assertAstEquals('QUERY(boolean_variable = true)', $ast);

        $ast = $this->extractKeywordWithRule('foo:"Hello world"', '/f/', '/=/', '/1/');
        $this->assertAstEquals('QUERY(foo = Hello world)', $ast);

        $ast = $this->extractKeywordWithRule('foo>=40', '/f/', '/^>$/', '/\d+/');
        $this->assertAstEquals('QUERY(foo >= 40)', $ast);
    }

    /** @test */
    function it_matches_all_values_of_queries_with_array_values()
    {
        $ast = $this->extractKeywordWithRule('foo in (1, 2, 3)', '/foo/', '/in/', '/\d+/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo in (1, 2, bar)', '/foo/', '/in/', '/\d+/');
        $this->assertAstEquals('QUERY(foo in [1, 2, bar])', $ast);

        $ast = $this->extractKeywordWithRule('foo:apple,banana,mango)', '/foo/', '/=/', '/a/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo:apple,banana,mango)', '/foo/', '/=/', '/^a/');
        $this->assertAstEquals('QUERY(foo = [apple, banana, mango])', $ast);
    }

    /** @test */
    function it_call_the_use_special_query_method_for_every_match()
    {
        $matches = collect();
        $callback = function ($query) use ($matches) {
            $matches->push($query->accept(new InlineDumpVisitor));
        };

        $ast = $this->extractKeywordWithRule('foo:1 bar:2 faz:3', '/^f/', null, null, $callback);

        $this->assertAstEquals('AND(NULL, QUERY(bar = 2), NULL)', $ast);
        $this->assertEquals([
            'QUERY(foo = 1)',
            'QUERY(faz = 3)',
        ], $matches->toArray());
    }

    /** @test */
    function it_keeps_track_of_the_last_query_that_matched()
    {
        $matches = collect();
        $callback = function ($query, $lastQuery) use ($matches) {
            $this->assertEquals($matches->last(), $lastQuery);
            $matches->push($query);
        };

        $this->extractKeywordWithRule('foo:1 bar:2 faz:3', '/^f/', null, null, $callback);
    }

    public function assertAstEquals($expectedAst, $ast)
    {
        $this->assertEquals($expectedAst, $ast->accept(new InlineDumpVisitor));
    }

    public function extractKeywordWithRule($input, $key, $operator = null, $value = null, $callback = null)
    {
        $builder = $this->getDummyBuilder();
        $manager = $this->getSearchStringManager();
        $rule = (object) [
            'key' => $key ?? '/.*/',
            'operator' => $operator ?? '/.*/',
            'value' => $value ?? '/.*/',
        ];

        return $this->parse($input)->accept(
            new class($builder, $manager, $rule, $callback) extends ExtractKeywordVisitor {
                protected $rule;
                protected $callback;

                public function __construct($builder, $manager, $rule, $callback)
                {
                    $this->rule = $rule;
                    $this->callback = $callback ?? function () {};
                    parent::__construct($builder, $manager, 'dummy_keyword');
                }

                public function getKeywordRule()
                {
                    return $this->rule;
                }

                public function resolveKeyword($query, $lastQuery)
                {
                    ($this->callback)($query, $lastQuery);
                }
            }
        );
    }
}