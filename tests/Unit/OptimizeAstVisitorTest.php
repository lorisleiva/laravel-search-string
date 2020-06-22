<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\AST\AndSymbol;
use Lorisleiva\LaravelSearchString\AST\NotSymbol;
use Lorisleiva\LaravelSearchString\AST\OrSymbol;
use Lorisleiva\LaravelSearchString\AST\QuerySymbol;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitors\OptimizeAstVisitor;

class OptimizeAstVisitorTest extends TestCase
{
    /** @test */
    public function it_flattens_and_or_operators()
    {
        $this->assertAstFor('A and (B and (C and D))', 'AND(A, B, C, D)');
        $this->assertAstFor('A or (B or (C or D))', 'OR(A, B, C, D)');
        $this->assertAstFor('A and (B and C) or D', 'OR(AND(A, B, C), D)');
        $this->assertAstFor('(A or (B or C)) and D', 'AND(OR(A, B, C), D)');
    }

    /** @test */
    public function it_does_not_conflicts_and_or_flattenings()
    {
        $this->assertAstFor('A or (B or C) and D or E', 'OR(A, AND(OR(B, C), D), E)');
        $this->assertAstFor('(A or B) or (C and D or E)', 'OR(A, B, AND(C, D), E)');
        $this->assertAstFor('(A and B) and C and (D or E)', 'AND(A, B, C, OR(D, E))');
    }

    /** @test */
    public function it_inlines_and_or_operators_containing_only_one_child()
    {
        $this->assertVisitedAstBecomes(
            new AndSymbol([new QuerySymbol('foo', '=', 'bar')]),
            'QUERY(foo = bar)'
        );

        $this->assertVisitedAstBecomes(
            new OrSymbol([new QuerySymbol('foo', '=', 'bar')]),
            'QUERY(foo = bar)'
        );
    }

    /** @test */
    public function it_removes_root_without_children()
    {
        $this->assertVisitedAstBecomes(new AndSymbol, 'EMPTY');
        $this->assertVisitedAstBecomes(new AndSymbol([new OrSymbol, new OrSymbol]), 'EMPTY');

        $this->assertVisitedAstBecomes(new OrSymbol, 'EMPTY');
        $this->assertVisitedAstBecomes(new OrSymbol([new AndSymbol, new AndSymbol]), 'EMPTY');

        $this->assertVisitedAstBecomes(new NotSymbol(new OrSymbol), 'EMPTY');
        $this->assertVisitedAstBecomes(new NotSymbol(new NotSymbol(new AndSymbol)), 'EMPTY');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = $this->parse($input)
            ->accept(new OptimizeAstVisitor())
            ->accept(new InlineDumpVisitor(true));

        $this->assertEquals($expectedAst, $ast);
    }

    public function assertVisitedAstBecomes($ast, $expectedAst)
    {
        $ast = $ast
            ->accept(new OptimizeAstVisitor())
            ->accept(new InlineDumpVisitor());

        $this->assertEquals($expectedAst, $ast);
    }
}
