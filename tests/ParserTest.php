<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Visitors\InlineDumpVisitor;

class ParserTest extends TestCase
{
    /** @test */
    public function it_parses_assignments_as_queries()
    {
        $this->assertAstFor('foo:bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo: bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo :bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo : bar', 'QUERY(foo = bar)');
        $this->assertAstFor('foo=10', 'QUERY(foo = 10)');
        $this->assertAstFor('foo="bar baz"', 'QUERY(foo = bar baz)');
    }

    /** @test */
    public function it_parses_comparisons_as_queries()
    {
        $this->assertAstFor('amount>0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount> 0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount >0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount > 0', 'QUERY(amount > 0)');
        $this->assertAstFor('amount >= 0', 'QUERY(amount >= 0)');
        $this->assertAstFor('amount < 0', 'QUERY(amount < 0)');
        $this->assertAstFor('amount <= 0', 'QUERY(amount <= 0)');
        $this->assertAstFor('users_todos <= 10', 'QUERY(users_todos <= 10)');
        $this->assertAstFor('date > "2018-05-14 00:41:10"', 'QUERY(date > 2018-05-14 00:41:10)');
    }

    /** @test */
    public function it_parses_lonely_terms_and_strings_as_solo_symbols()
    {
        $this->assertAstFor('lonely', 'SOLO(lonely)');
        $this->assertAstFor(' lonely ', 'SOLO(lonely)');
        $this->assertAstFor('"lonely"', 'SOLO(lonely)');
        $this->assertAstFor(' "lonely" ', 'SOLO(lonely)');
        $this->assertAstFor('"so lonely"', 'SOLO(so lonely)');
    }

    /** @test */
    public function it_parses_not_operators()
    {
        $this->assertAstFor('not A', 'NOT(SOLO(A))');
        $this->assertAstFor('not (not A)', 'NOT(NOT(SOLO(A)))');
        $this->assertAstFor('not not A', 'NOT(NOT(SOLO(A)))');
    }

    /** @test */
    public function it_parses_and_operators()
    {
        $this->assertAstFor('A and B and C', 'AND(SOLO(A), SOLO(B), SOLO(C))');
        $this->assertAstFor('(A AND B) and C', 'AND(AND(SOLO(A), SOLO(B)), SOLO(C))');
        $this->assertAstFor('A AND (B AND C)', 'AND(SOLO(A), AND(SOLO(B), SOLO(C)))');
    }

    /** @test */
    public function it_parses_or_operators()
    {
        $this->assertAstFor('A or B or C', 'OR(SOLO(A), SOLO(B), SOLO(C))');
        $this->assertAstFor('(A OR B) or C', 'OR(OR(SOLO(A), SOLO(B)), SOLO(C))');
        $this->assertAstFor('A OR (B OR C)', 'OR(SOLO(A), OR(SOLO(B), SOLO(C)))');
    }

    /** @test */
    public function it_prioritizes_or_over_and()
    {
        $this->assertAstFor('A or B and C or D', 'OR(SOLO(A), AND(SOLO(B), SOLO(C)), SOLO(D))');
        $this->assertAstFor('(A or B) and C', 'AND(OR(SOLO(A), SOLO(B)), SOLO(C))');
    }

    /** @test */
    public function it_can_parse_query_values_as_list_of_terms_and_strings()
    {
        $this->assertAstFor('foo:1,2,3', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor('foo: 1,2,3', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor('foo :1,2,3', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor('foo : 1,2,3', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor('foo : 1 , 2 , 3', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor('foo = "A B C",baz,"bar"', 'LIST(foo in [A B C, baz, bar])');
    }

    /** @test */
    public function it_parses_in_array_operator()
    {
        $this->assertAstFor('foo in(1,2,3)', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor('foo in (1,2,3)', 'LIST(foo in [1, 2, 3])');
        $this->assertAstFor(' foo in ( 1 , 2 , 3 ) ', 'LIST(foo in [1, 2, 3])');
    }

    /** @test */
    public function it_parses_complex_queries()
    {
        $this->assertAstFor(
            'A: 1 or B > 2 and not C or D <= "foo bar"',
            'OR(QUERY(A = 1), AND(QUERY(B > 2), NOT(SOLO(C))), QUERY(D <= foo bar))'
        );
        $this->assertAstFor(
            'sort:-name,date events > 10 and not started_at <= tomorrow',
            'AND(LIST(sort in [-name, date]), QUERY(events > 10), NOT(QUERY(started_at <= tomorrow)))'
        );
        $this->assertAstFor(
            'A (B) not C',
            'AND(SOLO(A), SOLO(B), NOT(SOLO(C)))'
        );
    }

    /** @test */
    public function it_returns_an_empty_symbol_if_no_ast_root_could_be_parsed()
    {
        $this->assertAstFor('', 'EMPTY');
    }

    /** @test */
    public function it_parses_relationship_symbols()
    {
        $this->assertAstFor('comments.author = "John Doe"', 'EXISTS(comments, QUERY(author = John Doe)) > 0');
        $this->assertAstFor('comments.author.tags > 3', 'EXISTS(comments, EXISTS(author, QUERY(tags > 3)) > 0) > 0');
        // TODO: more and make sure previous example becomes EXISTS(comments, EXISTS(author, EXISTS(tags, EMPTY) > 3) > 0) > 0
    }

    /** @test */
    public function it_fail_to_parse_unfinished_queries()
    {
        $this->assertParserFails('not ', 'EOF');
        $this->assertParserFails('foo = ', 'T_ASSIGNMENT');
        $this->assertParserFails('foo <= ', 'T_COMPARATOR');
        $this->assertParserFails('foo in ', 'T_IN');
        $this->assertParserFails('(', 'EOF');
    }

    /** @test */
    public function it_fail_to_parse_strings_as_query_keys()
    {
        $this->assertParserFails('"string as key":foo', 'T_ASSIGNMENT');
        $this->assertParserFails('foo and bar and "string as key" > 3', 'T_COMPARATOR');
        $this->assertParserFails('not "string as key" in (1,2,3)', 'T_IN');
    }

    /** @test */
    public function it_fails_to_parse_lonely_operators()
    {
        $this->assertParserFails('and', 'T_AND');
        $this->assertParserFails('or', 'T_OR');
        $this->assertParserFails('in', 'T_IN');
        $this->assertParserFails('=', 'T_ASSIGNMENT');
        $this->assertParserFails(':', 'T_ASSIGNMENT');
        $this->assertParserFails('<', 'T_COMPARATOR');
        $this->assertParserFails('<=', 'T_COMPARATOR');
        $this->assertParserFails('>', 'T_COMPARATOR');
        $this->assertParserFails('>=', 'T_COMPARATOR');
    }

    /** @test */
    public function it_fail_to_parse_weird_operator_combinations()
    {
        $this->assertParserFails('foo<>3', 'T_COMPARATOR');
        $this->assertParserFails('foo=>3', 'T_ASSIGNMENT');
        $this->assertParserFails('foo=<3', 'T_ASSIGNMENT');
        $this->assertParserFails('foo < in 3', 'T_COMPARATOR');
        $this->assertParserFails('foo in = 1,2,3', 'T_IN');
        $this->assertParserFails('foo == 1,2,3', 'T_ASSIGNMENT');
        $this->assertParserFails('foo := 1,2,3', 'T_ASSIGNMENT');
        $this->assertParserFails('foo:1:2:3:4', 'T_ASSIGNMENT');
    }

    public function assertAstFor($input, $expectedAst)
    {
        $ast = $this->parse($input)->accept(new InlineDumpVisitor());
        $this->assertEquals($expectedAst, $ast);
    }

    public function assertParserFails($input, $unexpectedToken = null)
    {
        try {
            $ast = $this->parse($input)->accept(new InlineDumpVisitor());
            $this->fail("Expected \"$input\" to fail. Instead got: \"$ast\"");
        } catch (InvalidSearchStringException $e) {
            if ($unexpectedToken) {
                $this->assertEquals($unexpectedToken, $e->getToken());
            } else {
                $this->assertTrue(true);
            }
        }
    }
}
