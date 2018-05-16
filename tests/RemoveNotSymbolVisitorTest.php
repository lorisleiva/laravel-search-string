<?php

namespace Lorisleiva\LaravelSearchString\Tests;

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
    function it_negates_search_symbols_and_boolean_queries()
    {
        $this->assertAstFor('foobar', 'SEARCH(foobar)');
        $this->assertAstFor('not foobar', 'SEARCH_NOT(foobar)');

        // Paid is defined in the `columns.boolean` option.
        $this->assertAstFor('paid', 'QUERY(paid = true)');
        $this->assertAstFor('not paid', 'QUERY(paid = false)');
    }

    /** @test */
    function it_negates_and_or_operator()
    {
        $this->assertAstFor('not (A and B)', 'OR(SEARCH_NOT(A), SEARCH_NOT(B))');
        $this->assertAstFor('not (A or B)', 'AND(SEARCH_NOT(A), SEARCH_NOT(B))');
        $this->assertAstFor('not (A or (B and C))', 'AND(SEARCH_NOT(A), OR(SEARCH_NOT(B), SEARCH_NOT(C)))');
        $this->assertAstFor('not (A and (B or C))', 'OR(SEARCH_NOT(A), AND(SEARCH_NOT(B), SEARCH_NOT(C)))');
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
        $this->assertAstFor('not not (A and B)', 'AND(SEARCH(A), SEARCH(B))');
        $this->assertAstFor('not not (A or B)', 'OR(SEARCH(A), SEARCH(B))');
        $this->assertAstFor('not not foobar', 'SEARCH(foobar)');
        $this->assertAstFor('not not paid', 'QUERY(paid = true)');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = $this->parse($input)
            ->accept(new RemoveNotSymbolVisitor())
            ->accept(new InlineDumpVisitor());

        $this->assertEquals($expectedAst, $ast);
    }
}