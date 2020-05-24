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

    public function negatedRelationQueriesDataProvider()
    {
        return [
            'Positive relation'       => ['has(comments)', 'HAS(comments)'],
            'Negated relation'        => ['not has(comments)', 'HAS_NOT(comments)'],
            'Double-negated relation' => ['not not has(comments)', 'HAS(comments)'],

            'Positive relation with constraints' => ['has(comments{foo:bar})', 'HAS(comments WHERE(QUERY(foo = bar)))'],
            'Negative relation with constraints' => ['not has(comments{foo:bar})', 'HAS_NOT(comments WHERE(QUERY(foo = bar)))'],

            'Positive count relation' => ['has(comments)>3', 'HAS(comments COUNT(> 3))'],
            'Negated count relation'  => ['not has(comments)>3', 'HAS(comments COUNT(<= 3))'],

            'Negated dot-nested related field as relation query' => [
                'not comments.foo:bar',
                'HAS(comments WHERE(QUERY(foo != bar)))'
            ],
            'Negated dot-nested related solo field as relation query' => [
                'not comments.spam',
                'HAS(comments WHERE(SOLO_NOT(spam)))'
            ],
            'Negated deeply dot-nested related field as relation query' => [
                'not comments.users.ideas.foo:bar',
                'HAS(comments.users.ideas WHERE(QUERY(foo != bar)))'
            ],
            'Negated deeply dot-nested solo related field as relation query' => [
                'not comments.users.ideas.active',
                'HAS(comments.users.ideas WHERE(SOLO_NOT(active)))'
            ],
            'Negated grouping of dot-nested related fields' => [
                'not ( comments.foo and comments.bar )',
                'OR(HAS(comments WHERE(SOLO_NOT(foo))), HAS(comments WHERE(SOLO_NOT(bar))))'
            ],
            'Grouping of dot-nested related field and simple query' => [
                'comments.foo and baz: "boo"',
                'AND(HAS(comments WHERE(SOLO(foo))), QUERY(baz = boo))'
            ],
            'Negated grouping of dot-nested related field and simple query' => [
                'not ( comments.foo and baz: "boo" )',
                'OR(HAS(comments WHERE(SOLO_NOT(foo))), QUERY(baz != boo))'
            ],
            'Double-negated dot-nested related field' => [
                'not not comments.foo', 'HAS(comments WHERE(SOLO(foo)))'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider negatedRelationQueriesDataProvider
     */
    public function it_negates_relation_queries($input, $expected)
    {
        $this->assertAstFor($input, $expected);
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = $this->parse($input)
            ->accept(new RemoveNotSymbolVisitor())
            ->accept(new InlineDumpVisitor());

        $this->assertEquals($expectedAst, $ast);
    }
}