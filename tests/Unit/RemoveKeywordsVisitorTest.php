<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitors\RemoveKeywordsVisitor;

class RemoveKeywordsVisitorTest extends TestCase
{
    /** @test */
    public function it_transforms_keyword_queries_into_empty_symbols()
    {
        $ast = $this->extractKeywordWithRule('foo:bar', '/^foo$/');
        $this->assertAstEquals('EMPTY', $ast);

        $ast = $this->extractKeywordWithRule('foo:1', '/f/');
        $this->assertAstEquals('EMPTY', $ast);
    }

    /** @test */
    public function it_leaves_queries_that_do_not_match_intact()
    {
        $ast = $this->extractKeywordWithRule('foo:bar', '/^baz$/');
        $this->assertAstEquals('QUERY(foo = bar)', $ast);

        $ast = $this->extractKeywordWithRule('foo:"Hello world"', 'f');
        $this->assertAstEquals('QUERY(foo = Hello world)', $ast);
    }

    public function assertAstEquals($expectedAst, $ast)
    {
        $this->assertEquals($expectedAst, $ast->accept(new InlineDumpVisitor));
    }

    public function extractKeywordWithRule($input, $key)
    {
        $model = $this->getModelWithKeywords(['banana_keyword' => $key]);

        $manager = $this->getSearchStringManager($model);
        return $this->parse($input)->accept(new RemoveKeywordsVisitor($manager));
    }
}
