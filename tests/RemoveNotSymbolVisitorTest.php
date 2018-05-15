<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Facade\SearchString;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class RemoveNotSymbolVisitorTest extends TestCase
{
    /** @test */
    function it_negates_the_operator_of_a_query()
    {
        $this->assertAstFor('not foo:bar', 'QUERY(foo != bar)');
        $this->assertAstFor('not foo:-1,2,3', 'QUERY(foo != [-1, 2, 3])');
        $this->assertAstFor('not foo="bar"', 'QUERY(foo != bar)');
        $this->assertAstFor('not foo<1', 'QUERY(foo >= 1)');
        $this->assertAstFor('not foo>1', 'QUERY(foo <= 1)');
        $this->assertAstFor('not foo<=1', 'QUERY(foo > 1)');
        $this->assertAstFor('not foo>=1', 'QUERY(foo < 1)');
        $this->assertAstFor('not foo in(1, 2, 3)', 'QUERY(foo not in [1, 2, 3])');
    }

    /** @test */
    function it_negates_and_or_operator()
    {
        $this->assertAstFor('not (A and B)', 'OR(QUERY(A = false), QUERY(B = false))');
        $this->assertAstFor('not (A or B)', 'AND(QUERY(A = false), QUERY(B = false))');
        $this->assertAstFor('not (A or (B and C))', 'AND(QUERY(A = false), OR(QUERY(B = false), QUERY(C = false)))');
        $this->assertAstFor('not (A and (B or C))', 'OR(QUERY(A = false), AND(QUERY(B = false), QUERY(C = false)))');
    }

    /** @test */
    function it_cancel_the_negation_of_another_not()
    {
        $this->assertAstFor('not not foo:bar', 'QUERY(foo = bar)');
        $this->assertAstFor('not not foo:-1,2,3', 'QUERY(foo = [-1, 2, 3])');
        $this->assertAstFor('not not foo="bar"', 'QUERY(foo = bar)');
        $this->assertAstFor('not not foo<1', 'QUERY(foo < 1)');
        $this->assertAstFor('not not foo>1', 'QUERY(foo > 1)');
        $this->assertAstFor('not not foo<=1', 'QUERY(foo <= 1)');
        $this->assertAstFor('not not foo>=1', 'QUERY(foo >= 1)');
        $this->assertAstFor('not not foo in(1, 2, 3)', 'QUERY(foo in [1, 2, 3])');
        $this->assertAstFor('not not (A and B)', 'AND(QUERY(A = true), QUERY(B = true))');
        $this->assertAstFor('not not (A or B)', 'OR(QUERY(A = true), QUERY(B = true))');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = SearchString::parse($input)
            ->accept(new RemoveNotSymbolVisitor())
            ->accept(new InlineDumpVisitor());

        $this->assertEquals($expectedAst, $ast);
    }
}