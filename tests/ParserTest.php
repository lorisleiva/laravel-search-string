<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Parser\Parser;
use Lorisleiva\LaravelSearchString\Test\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;

class ParserTest extends TestCase
{
    /** @test */
    function it_parses_assignments_as_queries()
    {
        $this->assertAstFor('foo:bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo: bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo :bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo : bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo=10', 'QUERY(foo = 10)');
        $this->assertAstFor('foo="bar baz"', 'QUERY(foo = bar baz)');
    }

    /** @test */
    function it_parses_comparaisons_as_queries()
    {
        $this->assertAstFor('amount>0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount> 0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount >0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount > 0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount >= 0', 'QUERY(amount >= 0)');
        $this->assertAstFor('amount < 0', 'QUERY(amount < 0)');
        $this->assertAstFor('amount <= 0', 'QUERY(amount <= 0)');
        $this->assertAstFor('users.todos <= 10', 'QUERY(users.todos <= 10)');
        $this->assertAstFor('date > "2018-05-14 00:41:10"', 'QUERY(date > 2018-05-14 00:41:10)');
    }

    /** @test */
    function it_parses_unique_terms_as_boolean()
    {
        $this->assertAstFor('has_event', 'QUERY(has_event = true)');
        $this->assertAstFor(' has_event ', 'QUERY(has_event = true)');
        $this->assertAstFor('(has_event)', 'QUERY(has_event = true)');
        $this->assertAstFor('(has_event )', 'QUERY(has_event = true)');
        $this->assertAstFor('( has_event)', 'QUERY(has_event = true)');
        $this->assertAstFor('( has_event )', 'QUERY(has_event = true)');
    }

    /** @test */
    function it_parses_not_operators()
    {
        $this->assertAstFor('not A', 'NOT(QUERY(A = true))');
        $this->assertAstFor('not (not A)', 'NOT(NOT(QUERY(A = true)))');
        $this->assertAstFor('not not A', 'NOT(NOT(QUERY(A = true)))');
    }

    /** @test */
    function it_parses_and_operators()
    {
        $this->assertAstFor(
            'A and B and C', 
            'AND(QUERY(A = true), QUERY(B = true), QUERY(C = true))'
        );
        $this->assertAstFor(
            '(A AND B) and C', 
            'AND(AND(QUERY(A = true), QUERY(B = true)), QUERY(C = true))'
        );
        $this->assertAstFor(
            'A AND (B AND C)', 
            'AND(QUERY(A = true), AND(QUERY(B = true), QUERY(C = true)))'
        );
    }

    /** @test */
    function it_parses_or_operators()
    {
        $this->assertAstFor(
            'A or B or C', 
            'OR(QUERY(A = true), QUERY(B = true), QUERY(C = true))'
        );
        $this->assertAstFor(
            '(A OR B) or C', 
            'OR(OR(QUERY(A = true), QUERY(B = true)), QUERY(C = true))'
        );
        $this->assertAstFor(
            'A OR (B OR C)', 
            'OR(QUERY(A = true), OR(QUERY(B = true), QUERY(C = true)))'
        );
    }

    /** @test */
    function it_prioritizes_or_over_and()
    {
        $this->assertAstFor(
            'A or B and C or D', 
            'OR(QUERY(A = true), AND(QUERY(B = true), QUERY(C = true)), QUERY(D = true))'
        );
        $this->assertAstFor(
            '(A or B) and C', 
            'AND(OR(QUERY(A = true), QUERY(B = true)), QUERY(C = true))'
        );
    }

    /** @test */
    function it_can_parse_query_values_as_list_of_terms_and_strings()
    {
        $this->assertAstFor('foo:1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo: 1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo :1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo : 1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo = "A B C",baz,"bar"', 'QUERY(foo = [A B C, baz, bar])');
    }

    /** @test */
    function it_parses_complex_queries()
    {
        $this->assertAstFor(
            'A: 1 or B > 2 and not C or D <= "foo bar"', 
            'OR(QUERY(A = 1), AND(QUERY(B > 2), NOT(QUERY(C = true))), QUERY(D <= foo bar))'
        );
        $this->assertAstFor(
            'sort:-name,date events > 10 and not started_at <= tomorrow', 
            'AND(QUERY(sort = [-name, date]), QUERY(events > 10), NOT(QUERY(started_at <= tomorrow)))'
        );
    }

    public function assertAstFor($input, $expectedAst)
    {
        $tokens = (new Lexer(config('search-string.token_map')))->lex($input);
        $ast = (new Parser($tokens))->parse();
        $this->assertEquals($expectedAst, $ast->accept(new InlineDumpVisitor()));
    }
}