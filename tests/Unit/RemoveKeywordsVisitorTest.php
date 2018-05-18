<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;
use Lorisleiva\LaravelSearchString\Tests\Concerns\GeneratesEloquentBuilder;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveKeywordsVisitor;

class RemoveKeywordsVisitorTest extends TestCase
{
    use GeneratesEloquentBuilder;

    /** @test */
    function it_transforms_keyword_queries_into_null_symbols()
    {
        $ast = $this->extractKeywordWithRule('foo:bar', '/^foo$/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo:1', '/f/', '/=/', '/1/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo>40', '/f/', '/^>$/', '/\d+/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo in (1, 2, 3)', '/foo/', '/in/', '/\d+/');
        $this->assertAstEquals('NULL', $ast);

        $ast = $this->extractKeywordWithRule('foo:apple,banana,mango)', '/foo/', '/=/', '/a/');
        $this->assertAstEquals('NULL', $ast);
    }

    /** @test */
    function it_leaves_queries_that_do_not_match_intact()
    {
        $ast = $this->extractKeywordWithRule('foo:bar', '/^baz$/');
        $this->assertAstEquals('QUERY(foo = bar)', $ast);

        $ast = $this->extractKeywordWithRule('foo:"Hello world"', '/f/', '/=/', '/1/');
        $this->assertAstEquals('QUERY(foo = Hello world)', $ast);

        $ast = $this->extractKeywordWithRule('foo>=40', '/f/', '/^>$/', '/\d+/');
        $this->assertAstEquals('QUERY(foo >= 40)', $ast);

        $ast = $this->extractKeywordWithRule('foo in (1, 2, bar)', '/foo/', '/in/', '/\d+/');
        $this->assertAstEquals('QUERY(foo in [1, 2, bar])', $ast);

        $ast = $this->extractKeywordWithRule('foo:apple,banana,mango)', '/foo/', '/=/', '/^a/');
        $this->assertAstEquals('QUERY(foo = [apple, banana, mango])', $ast);
    }

    public function assertAstEquals($expectedAst, $ast)
    {
        $this->assertEquals($expectedAst, $ast->accept(new InlineDumpVisitor));
    }

    public function extractKeywordWithRule($input, $key, $operator = null, $value = null)
    {
        $rule = [
            'key' => $key,
            'operator' => $operator ?? '/.*/',
            'value' => $value ?? '/.*/',
        ];

        $model = new class($rule) extends Model {
            use SearchString;
            public function __construct($rule)
            {
                $this->searchStringKeywords = ['banana_keyword' => $rule];
            }
        };

        $builder = $this->getDummyBuilder($model);
        $manager = $this->getSearchStringManager($model);
        return $this->parse($input)->accept(new RemoveKeywordsVisitor($manager));
    }
}