<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;

class AstBeforeVisitorsTest extends VisitorTest
{
    public function visitors($manager, $builder, $model)
    {
        return [
            new InlineDumpVisitor()
        ];
    }

    public function success()
    {
        return [
            // Assignments.
            ['foo:bar', 'QUERY(foo = bar)'],
            ['foo: bar', 'QUERY(foo = bar)'],
            ['foo :bar', 'QUERY(foo = bar)'],
            ['foo : bar', 'QUERY(foo = bar)'],
            ['foo=10', 'QUERY(foo = 10)'],
            ['foo="bar baz"', 'QUERY(foo = bar baz)'],

            // Comparisons.
            ['amount>0', 'QUERY(amount > 0)'],
            ['amount> 0', 'QUERY(amount > 0)'],
            ['amount >0', 'QUERY(amount > 0)'],
            ['amount > 0', 'QUERY(amount > 0)'],
            ['amount >= 0', 'QUERY(amount >= 0)'],
            ['amount < 0', 'QUERY(amount < 0)'],
            ['amount <= 0', 'QUERY(amount <= 0)'],
            ['users_todos <= 10', 'QUERY(users_todos <= 10)'],
            ['date > "2018-05-14 00:41:10"', 'QUERY(date > 2018-05-14 00:41:10)'],

            // Solo.
            ['lonely', 'SOLO(lonely)'],
            [' lonely ', 'SOLO(lonely)'],
            ['"lonely"', 'SOLO(lonely)'],
            [' "lonely" ', 'SOLO(lonely)'],
            ['"so lonely"', 'SOLO(so lonely)'],

            // Not.
            ['not A', 'NOT(SOLO(A))'],
            ['not (not A)', 'NOT(NOT(SOLO(A)))'],
            ['not not A', 'NOT(NOT(SOLO(A)))'],

            //And.
            ['A and B and C', 'AND(SOLO(A), SOLO(B), SOLO(C))'],
            ['(A AND B) and C', 'AND(AND(SOLO(A), SOLO(B)), SOLO(C))'],
            ['A AND (B AND C)', 'AND(SOLO(A), AND(SOLO(B), SOLO(C)))'],

            // Or.
            ['A or B or C', 'OR(SOLO(A), SOLO(B), SOLO(C))'],
            ['(A OR B) or C', 'OR(OR(SOLO(A), SOLO(B)), SOLO(C))'],
            ['A OR (B OR C)', 'OR(SOLO(A), OR(SOLO(B), SOLO(C)))'],

            // Or precedes And.
            ['A or B and C or D', 'OR(SOLO(A), AND(SOLO(B), SOLO(C)), SOLO(D))'],
            ['(A or B) and C', 'AND(OR(SOLO(A), SOLO(B)), SOLO(C))'],

            // Lists.
            ['foo:1,2,3', 'LIST(foo in [1, 2, 3])'],
            ['foo: 1,2,3', 'LIST(foo in [1, 2, 3])'],
            ['foo :1,2,3', 'LIST(foo in [1, 2, 3])'],
            ['foo : 1,2,3', 'LIST(foo in [1, 2, 3])'],
            ['foo : 1 , 2 , 3', 'LIST(foo in [1, 2, 3])'],
            ['foo = "A B C",baz,"bar"', 'LIST(foo in [A B C, baz, bar])'],
            ['foo in(1,2,3)', 'LIST(foo in [1, 2, 3])'],
            ['foo in (1,2,3)', 'LIST(foo in [1, 2, 3])'],
            [' foo in ( 1 , 2 , 3 ) ', 'LIST(foo in [1, 2, 3])'],

            // Complex examples.
            [
                'A: 1 or B > 2 and not C or D <= "foo bar"',
                'OR(QUERY(A = 1), AND(QUERY(B > 2), NOT(SOLO(C))), QUERY(D <= foo bar))'
            ],
            [
                'sort:-name,date events > 10 and not started_at <= tomorrow',
                'AND(LIST(sort in [-name, date]), QUERY(events > 10), NOT(QUERY(started_at <= tomorrow)))'
            ],
            [
                'A (B) not C',
                'AND(SOLO(A), SOLO(B), NOT(SOLO(C)))'
            ],

            // Empty.
            ['', 'EMPTY'],

            // Relationships.
            ['comments.author = "John Doe"', 'EXISTS(comments, QUERY(author = John Doe)) > 0'],
            ['comments.author.tags > 3', 'EXISTS(comments, EXISTS(author, QUERY(tags > 3)) > 0) > 0'],
        ];
    }

    public function failure()
    {
        return [
            // Unfinished.
            ['not ', 'EOF'],
            ['foo = ', 'T_ASSIGNMENT'],
            ['foo <= ', 'T_COMPARATOR'],
            ['foo in ', 'T_IN'],
            ['(', 'EOF'],

            // String as key.
            ['"string as key":foo', 'T_ASSIGNMENT'],
            ['foo and bar and "string as key" > 3', 'T_COMPARATOR'],
            ['not "string as key" in (1,2,3)', 'T_IN'],

            // Lonely operators.
            ['and', 'T_AND'],
            ['or', 'T_OR'],
            ['in', 'T_IN'],
            ['=', 'T_ASSIGNMENT'],
            [':', 'T_ASSIGNMENT'],
            ['<', 'T_COMPARATOR'],
            ['<=', 'T_COMPARATOR'],
            ['>', 'T_COMPARATOR'],
            ['>=', 'T_COMPARATOR'],

            // Invalid operators.
            ['foo<>3', 'T_COMPARATOR'],
            ['foo=>3', 'T_ASSIGNMENT'],
            ['foo=<3', 'T_ASSIGNMENT'],
            ['foo < in 3', 'T_COMPARATOR'],
            ['foo in = 1,2,3', 'T_IN'],
            ['foo == 1,2,3', 'T_ASSIGNMENT'],
            ['foo := 1,2,3', 'T_ASSIGNMENT'],
            ['foo:1:2:3:4', 'T_ASSIGNMENT'],
        ];
    }

    /**
     * @test
     * @dataProvider success
     * @param $input
     * @param $expected
     */
    public function ast_before_visitors_success($input, $expected)
    {
        $this->assertAstFor($input, $expected);
    }

    /**
     * @test
     * @dataProvider failure
     * @param $input
     * @param $unexpectedToken
     */
    public function ast_before_visitors_failure($input, $unexpectedToken)
    {
        $this->assertAstFails($input, $unexpectedToken);
    }
}