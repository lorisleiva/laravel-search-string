<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class RemoveNotSymbolVisitorTest extends TestCase
{
    /** @test */
    public function it_negates_the_operator_of_a_query()
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
    public function it_negates_solo_symbols()
    {
        $this->assertAstFor('foobar', 'SOLO(foobar)');
        $this->assertAstFor('not foobar', 'SOLO_NOT(foobar)');
        $this->assertAstFor('"John Doe"', 'SOLO(John Doe)');
        $this->assertAstFor('not "John Doe"', 'SOLO_NOT(John Doe)');
    }

    /** @test */
    public function it_negates_and_or_operator()
    {
        $this->assertAstFor('not (A and B)', 'OR(SOLO_NOT(A), SOLO_NOT(B))');
        $this->assertAstFor('not (A or B)', 'AND(SOLO_NOT(A), SOLO_NOT(B))');
        $this->assertAstFor('not (A or (B and C))', 'AND(SOLO_NOT(A), OR(SOLO_NOT(B), SOLO_NOT(C)))');
        $this->assertAstFor('not (A and (B or C))', 'OR(SOLO_NOT(A), AND(SOLO_NOT(B), SOLO_NOT(C)))');
    }

    /** @test */
    public function it_cancel_the_negation_of_another_not()
    {
        $this->assertAstFor('not not foo:bar', 'QUERY(foo = bar)');
        $this->assertAstFor('not not foo:-1,2,3', 'QUERY(foo = [-1, 2, 3])');
        $this->assertAstFor('not not foo="bar"', 'QUERY(foo = bar)');
        $this->assertAstFor('not not foo<1', 'QUERY(foo < 1)');
        $this->assertAstFor('not not foo>1', 'QUERY(foo > 1)');
        $this->assertAstFor('not not foo<=1', 'QUERY(foo <= 1)');
        $this->assertAstFor('not not foo>=1', 'QUERY(foo >= 1)');
        $this->assertAstFor('not not foo in(1, 2, 3)', 'QUERY(foo in [1, 2, 3])');
        $this->assertAstFor('not not (A and B)', 'AND(SOLO(A), SOLO(B))');
        $this->assertAstFor('not not (A or B)', 'OR(SOLO(A), SOLO(B))');
        $this->assertAstFor('not not foobar', 'SOLO(foobar)');
        $this->assertAstFor('not not "John Doe"', 'SOLO(John Doe)');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = $this->parse($input)
            ->accept(new RemoveNotSymbolVisitor())
            ->accept(new InlineDumpVisitor());

        $this->assertEquals($expectedAst, $ast);
    }
}