<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\TestCase;

class LexerTest extends TestCase
{
    /** @test */
    public function it_lexes_quoted_strings()
    {
        $this->assertTokensFor('"Hello world"', 'T_STRING');
    }

    /** @test */
    public function it_lexes_assignments()
    {
        $this->assertTokensFor('foo:bar', 'T_TERM T_ASSIGN T_TERM');
        $this->assertTokensFor('foo=bar', 'T_TERM T_ASSIGN T_TERM');
        $this->assertTokensFor('foo:"bar"', 'T_TERM T_ASSIGN T_STRING');
    }

    /** @test */
    public function it_lexes_spaces_and_parenthesis()
    {
        $this->assertTokensFor(' foo = bar ', 'T_SPACE T_TERM T_SPACE T_ASSIGN T_SPACE T_TERM T_SPACE');
        $this->assertTokensFor('(foo:bar)', 'T_LPARENT T_TERM T_ASSIGN T_TERM T_RPARENT');
    }

    /** @test */
    public function it_lexes_comparisons()
    {
        $this->assertTokensFor('foo<bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo<=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo>bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo>=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo<"bar"', 'T_TERM T_COMPARATOR T_STRING');
    }

    /** @test */
    public function it_lexes_boolean_operator()
    {
        $this->assertTokensFor('foo and bar', 'T_TERM T_SPACE T_AND T_SPACE T_TERM');
        $this->assertTokensFor('foo or bar', 'T_TERM T_SPACE T_OR T_SPACE T_TERM');
        $this->assertTokensFor('foo and not bar', 'T_TERM T_SPACE T_AND T_SPACE T_NOT T_SPACE T_TERM');
    }

    /** @test */
    public function it_lexes_in_operator_with_commas()
    {
        $this->assertTokensFor(
            'foo in (a,b,c)',
            'T_TERM T_SPACE T_IN T_SPACE T_LPARENT T_TERM T_LIST_SEPARATOR T_TERM T_LIST_SEPARATOR T_TERM T_RPARENT'
        );
    }

    /** @test */
    public function it_lexes_complex_queries()
    {
        $this->assertTokensFor(
            'foo12bar.x.y.z and (foo:1 or bar> 3)',

            // foo12bar.x.y.z and
            'T_TERM T_SPACE T_AND T_SPACE ' .
            // (foo:1 or
            'T_LPARENT T_TERM T_ASSIGN T_TERM T_SPACE T_OR '.
            // bar> 3)
            'T_SPACE T_TERM T_COMPARATOR T_SPACE T_TERM T_RPARENT'
        );
    }

    /** @test */
    public function it_lexes_greedily_on_terms()
    {
        $this->assertTokensFor('and', 'T_AND');
        $this->assertTokensFor('andora', 'T_TERM');
        $this->assertTokensFor('or', 'T_OR');
        $this->assertTokensFor('oracle', 'T_TERM');
        $this->assertTokensFor('not', 'T_NOT');
        $this->assertTokensFor('notice', 'T_TERM');
    }

    /** @test */
    public function terminating_keywords_operators_stay_keywords()
    {
        $this->assertTokensFor('and', 'T_AND');
        $this->assertTokensFor('or', 'T_OR');
        $this->assertTokensFor('not', 'T_NOT');
        $this->assertTokensFor('in', 'T_IN');
        $this->assertTokensFor('and)', 'T_AND T_RPARENT');
        $this->assertTokensFor('or)', 'T_OR T_RPARENT');
        $this->assertTokensFor('not)', 'T_NOT T_RPARENT');
        $this->assertTokensFor('in)', 'T_IN T_RPARENT');
    }

    /** @test */
    public function it_lexes_relation_queries()
    {
        $this->assertTokensFor('has(comments)', 'T_HAS T_LPARENT T_TERM T_RPARENT');
        $this->assertTokensFor('has(comments{name})', 'T_HAS T_LPARENT T_TERM T_LBRACE T_TERM T_RBRACE T_RPARENT');
        $this->assertTokensFor('has(comments)>3', 'T_HAS T_LPARENT T_TERM T_RPARENT T_COMPARATOR T_TERM');
        $this->assertTokensFor('has(comments{name})>3', 'T_HAS T_LPARENT T_TERM T_LBRACE T_TERM T_RBRACE T_RPARENT T_COMPARATOR T_TERM');
        $this->assertTokensFor('not has(comments)', 'T_NOT T_SPACE T_HAS T_LPARENT T_TERM T_RPARENT');
        $this->assertTokensFor('not has(comments{name})', 'T_NOT T_SPACE T_HAS T_LPARENT T_TERM T_LBRACE T_TERM T_RBRACE T_RPARENT');
    }

    public function assertTokensFor($input, $expectedTokens)
    {
        $tokens = $this->lex($input)->map->type->implode(' ');
        $this->assertSame($expectedTokens, $tokens);
    }
}