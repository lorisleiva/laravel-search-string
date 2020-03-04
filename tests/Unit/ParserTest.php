<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\InlineDumpVisitor;

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
    public function it_ignores_trailing_and_or_operators()
    {
        $this->assertAstFor('foo and', 'SOLO(foo)');
        $this->assertAstFor('foo or', 'SOLO(foo)');
    }

    /** @test */
    public function it_can_parse_query_values_as_list_of_terms_and_strings()
    {
        $this->assertAstFor('foo:1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo: 1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo :1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo : 1,2,3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo : 1 , 2 , 3', 'QUERY(foo = [1, 2, 3])');
        $this->assertAstFor('foo = "A B C",baz,"bar"', 'QUERY(foo = [A B C, baz, bar])');
    }

    /** @test */
    public function it_parses_in_array_operator()
    {
        $this->assertAstFor('foo in(1,2,3)', 'QUERY(foo in [1, 2, 3])');
        $this->assertAstFor('foo in (1,2,3)', 'QUERY(foo in [1, 2, 3])');
        $this->assertAstFor(' foo in ( 1 , 2 , 3 ) ', 'QUERY(foo in [1, 2, 3])');
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
            'AND(QUERY(sort = [-name, date]), QUERY(events > 10), NOT(QUERY(started_at <= tomorrow)))'
        );
        $this->assertAstFor(
            'A (B) not C',
            'AND(SOLO(A), SOLO(B), NOT(SOLO(C)))'
        );
    }

    public function relationQueriesDataProvider()
    {
        return [
            'Simple relation' => [
                'has(comments)', 'HAS(comments)'
            ],
            'Simple relation with empty constraints' => [
                'has(comments{ })', 'HAS(comments)'
            ],

            'Simple relation with constraints' => [
                'has(comments{foo:bar})', 'HAS(comments WHERE(QUERY(foo = bar)))'
            ],
            'Simple relation with multiple constraints' => [
                'has(comments{foo:bar baz:bek})', 'HAS(comments WHERE(AND(QUERY(foo = bar), QUERY(baz = bek))))'
            ],

            'Dot-nested relation with child constraints' => [
                'has(comments.author{foo:bar})', 'HAS(comments.author WHERE(QUERY(foo = bar)))'
            ],
            'Nested relation with child constraints' => [
                'has(comments{has(author{foo:bar})})', 'HAS(comments.author WHERE(QUERY(foo = bar)))'
            ],

            'Dot-nested relations with no parent constraints are collapsed' => [
                'has(comments.author.profiles{foo:bar})', 'HAS(comments.author.profiles WHERE(QUERY(foo = bar)))'
            ],
            'Nested relations with no parent constraints are collapsed' => [
                'has(comments {
                    has(author {
                        has(profiles{
                            foo:bar
                        })
                    })
                })', 'HAS(comments.author.profiles WHERE(QUERY(foo = bar)))'
            ],

            'Nested relations with parent constraints are not collapsed' => [
                'has(comments{baz:bek and has(author{foo:bar})})', 'HAS(comments WHERE(AND(QUERY(baz = bek), HAS(author WHERE(QUERY(foo = bar))))))'
            ],

            'Nested relations with some parent constraints are collapsed where possible' => [
                'has(comments {
                    baz:bek
                    has(author {
                        has(profiles{
                            foo:bar
                        })
                    })
                })', 'HAS(comments WHERE(AND(QUERY(baz = bek), HAS(author.profiles WHERE(QUERY(foo = bar))))))'
            ],

            'Simple count relation' => [
                'has(comments)>3', 'HAS(comments COUNT(> 3))'
            ],
            'Count related with constraints' => [
                'has(comments{foo:bar})>3', 'HAS(comments WHERE(QUERY(foo = bar)) COUNT(> 3))'
            ],

            'Negated count relation' => [
                'not has(comments)', 'NOT(HAS(comments))'
            ],
            'Negated count relation with constraints' => [
                'not has(comments{foo:bar})', 'NOT(HAS(comments WHERE(QUERY(foo = bar))))'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider relationQueriesDataProvider
     */
    public function it_parses_relation_queries($input, $expected)
    {
        $this->assertAstFor($input, $expected);
    }

    public function invalidRelationQueriesDataProvider()
    {
        return [
            'Unclosed parenthesis' => [
                'has(comments', 'T_EOL'
            ],
            'Unclosed brace' => [
                'has(comments {)', 'T_RPARENT'
            ],
            'Unopened parenthesis' => [
                'has comments)', 'T_TERM'
            ],
            'Orphaned has' => [
                'has', 'T_EOL'
            ],
            'Incomplete query' => [
                'has(comments { active = })', 'T_RBRACE'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidRelationQueriesDataProvider
     */
    public function it_fails_to_parse_invalid_relation_queries($input, $expected)
    {
        $this->assertParserFails($input, $expected);
    }

    /** @test */
    public function it_throws_an_exception_if_the_relation_count_is_not_an_integer()
    {
        $this->assertParserFails('has(comments) > foo');
        $this->assertParserFails('has(comments) > "bar"');
        $this->assertParserFails('has(comments) > =');
    }

    /** @test */
    public function it_returns_a_null_symbol_if_no_ast_root_could_be_parsed()
    {
        $this->assertAstFor('', 'NULL');
    }

    /** @test */
    public function it_fails_to_parse_unfinished_queries()
    {
        $this->assertParserFails('not ', 'T_EOL');
        $this->assertParserFails('foo = ', 'T_EOL');
        $this->assertParserFails('foo <= ', 'T_EOL');
        $this->assertParserFails('foo in ', 'T_EOL');
        $this->assertParserFails('(', 'T_EOL');
    }

    /** @test */
    public function it_fails_to_parse_strings_as_query_keys()
    {
        $this->assertParserFails('"string as key":foo', 'T_ASSIGN');
        $this->assertParserFails('foo and bar and "string as key" > 3', 'T_COMPARATOR');
        $this->assertParserFails('not "string as key" in (1,2,3)', 'T_IN');
    }

    /** @test */
    public function it_fails_to_parse_lonely_operators()
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
    public function it_fails_to_parse_weird_operator_combinations()
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
        $ast = $this->parse($input)->accept(new InlineDumpVisitor());
        $this->assertEquals($expectedAst, $ast);
    }

    public function assertParserFails($input, $problematicType = null)
    {
        try {
            $ast = $this->parse($input)->accept(new InlineDumpVisitor());
            $this->fail("Expected \"$input\" to fail. Instead got: \"$ast\"");
        } catch (InvalidSearchStringException $e) {
            if ($problematicType) {
                $this->assertEquals($problematicType, $e->getToken()->type);
            } else {
                $this->assertTrue(true);
            }
        }
    }
}