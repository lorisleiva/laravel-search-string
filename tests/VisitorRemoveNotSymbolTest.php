<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;
use Lorisleiva\LaravelSearchString\Visitors\RemoveNotSymbolVisitor;

/**
 * @see RemoveNotSymbolVisitor
 */
class VisitorRemoveNotSymbolTest extends VisitorTest
{
    public function visitors($manager, $builder, $model)
    {
        return [
            new RemoveNotSymbolVisitor(),
            new InlineDumpVisitor(),
        ];
    }

    public function success()
    {
        return [
            // Negate query symbols.
            ['not foo:bar', 'QUERY(foo != bar)'],
            ['not foo:-1,2,3', 'LIST(foo not in [-1, 2, 3])'],
            ['not foo="bar"', 'QUERY(foo != bar)'],
            ['not foo<1', 'QUERY(foo >= 1)'],
            ['not foo>1', 'QUERY(foo <= 1)'],
            ['not foo<=1', 'QUERY(foo > 1)'],
            ['not foo>=1', 'QUERY(foo < 1)'],
            ['not foo in(1, 2, 3)', 'LIST(foo not in [1, 2, 3])'],

            // Negate solo symbols.
            ['foobar', 'SOLO(foobar)'],
            ['not foobar', 'SOLO_NOT(foobar)'],
            ['"John Doe"', 'SOLO(John Doe)'],
            ['not "John Doe"', 'SOLO_NOT(John Doe)'],

            // Negate and/or symbols.
            ['not (A and B)', 'OR(SOLO_NOT(A), SOLO_NOT(B))'],
            ['not (A or B)', 'AND(SOLO_NOT(A), SOLO_NOT(B))'],
            ['not (A or (B and C))', 'AND(SOLO_NOT(A), OR(SOLO_NOT(B), SOLO_NOT(C)))'],
            ['not (A and (B or C))', 'OR(SOLO_NOT(A), AND(SOLO_NOT(B), SOLO_NOT(C)))'],

            // Cancel the negation of another not.
            ['not not foo:bar', 'QUERY(foo = bar)'],
            ['not not foo:-1,2,3', 'LIST(foo in [-1, 2, 3])'],
            ['not not foo="bar"', 'QUERY(foo = bar)'],
            ['not not foo<1', 'QUERY(foo < 1)'],
            ['not not foo>1', 'QUERY(foo > 1)'],
            ['not not foo<=1', 'QUERY(foo <= 1)'],
            ['not not foo>=1', 'QUERY(foo >= 1)'],
            ['not not foo in(1, 2, 3)', 'LIST(foo in [1, 2, 3])'],
            ['not not (A and B)', 'AND(SOLO(A), SOLO(B))'],
            ['not not (A or B)', 'OR(SOLO(A), SOLO(B))'],
            ['not not foobar', 'SOLO(foobar)'],
            ['not not "John Doe"', 'SOLO(John Doe)'],
        ];
    }

    /**
     * @test
     * @dataProvider success
     * @param $input
     * @param $expected
     */
    public function visitor_remove_not_symbol_success($input, $expected)
    {
        $this->assertAstEquals($input, $expected);
    }
}
