<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Parser\Parser;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
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
    function it_ignores_trailing_and_or_operators()
    {
        $this->assertAstFor('foo and', 'QUERY(foo = true)');
        $this->assertAstFor('foo or', 'QUERY(foo = true)');
    }

    /** @test */
    function it_can_parse_query_values_as_list_of_terms_and_strings()
    {
        $this->assertAstFor('foo:1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo: 1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo :1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo : 1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo : 1 , 2 , 3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo = "A B C",baz,"bar"', 'QUERY(foo = [A B C, baz, bar])');
    }

    /** @test */
    function it_parses_in_array_operator()
    {
        $this->assertAstFor('foo in(1,2,3)', 'QUERY(foo in [1, 2, 3])');
        $this->assertAstFor('foo in (1,2,3)', 'QUERY(foo in [1, 2, 3])');
        $this->assertAstFor(' foo in ( 1 , 2 , 3 ) ', 'QUERY(foo in [1, 2, 3])');
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
        $this->assertAstFor(
            'A (B) not C', 
            'AND(QUERY(A = true), QUERY(B = true), NOT(QUERY(C = true)))'
        );
    }

    /** @test */
    function it_returns_false_if_no_ast_root_could_be_parsed()
    {
        $this->assertFalse($this->parse(''));
    }

    /** @test */
    function it_fail_to_parse_unfinished_queries()
    {
        $this->assertParserFails('not ', 'T_EOL');
        $this->assertParserFails('foo = ', 'T_EOL');
        $this->assertParserFails('foo <= ', 'T_EOL');
        $this->assertParserFails('foo in ', 'T_EOL');
        $this->assertParserFails('(', 'T_EOL');
    }

    /** @test */
    function it_fails_to_parse_lonely_operators()
    {
        $this->assertParserFails('and', 'T_AND');
        $this->assertParserFails('or', 'T_OR');
        $this->assertParserFails('in', 'T_IN');
        $this->assertParserFails('=', 'T_ASSIGN');
        $this->assertParserFails(':', 'T_ASSIGN');
        $this->assertParserFails('<', 'T_COMPARATOR');
        $this->assertParserFails('<=', 'T_COMPARATOR');
        $this->assertParserFails('>', 'T_COMPARATOR');
        $this->assertParserFails('>=', 'T_COMPARATOR');
    }

    /** @test */
    function it_fails_to_parse_lonely_strings()
    {
        $this->assertParserFails('"lonely"', 'T_STRING');
        $this->assertParserFails('foo and bar and "so lonely"', 'T_STRING');
        $this->assertParserFails('not "still lonely"', 'T_STRING');
    }

    /** @test */
    function it_fail_to_parse_weird_operator_combinations()
    {
        $this->assertParserFails('foo<>3', 'T_COMPARATOR');
        $this->assertParserFails('foo=>3', 'T_COMPARATOR');
        $this->assertParserFails('foo=<3', 'T_COMPARATOR');
        $this->assertParserFails('foo < in 3', 'T_IN');
        $this->assertParserFails('foo in = 1,2,3', 'T_ASSIGN');
        $this->assertParserFails('foo == 1,2,3', 'T_ASSIGN');
        $this->assertParserFails('foo := 1,2,3', 'T_ASSIGN');
        $this->assertParserFails('foo:1:2:3:4', 'T_ASSIGN');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $this->assertEquals(
            $expectedAst, 
            $this->parse($input)->accept(new InlineDumpVisitor())
        );
    }

    public function assertParserFails($input, $problematicType = null)
    {
        try {
            $ast = $this->parse($input);
            $output = $ast->accept(new InlineDumpVisitor());
            $this->fail("Expected \"$input\" to fail. Instead got: \"$output\"");
        } catch (InvalidSearchStringException $e) {
            if ($problematicType) {
                $this->assertEquals($problematicType, $e->getToken()->type);
            } else {
                $this->assertTrue(true);
            }
        }
    }
}