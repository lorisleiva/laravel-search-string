<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\TestCase;

class LexerTest extends TestCase
{
    /** @test */
    public function it_lexes_quoted_strings()
    {
        $this->assertTokensFor('"Hello world"', 'T_DOUBLE_LQUOTE T_STRING T_DOUBLE_RQUOTE');
        $this->assertTokensFor("'Hello world'", 'T_SINGLE_LQUOTE T_STRING T_SINGLE_RQUOTE');
    }

    /** @test */
    public function it_lexes_assignments()
    {
        $this->assertTokensFor('foo:bar', 'T_TERM T_ASSIGNMENT T_TERM');
        $this->assertTokensFor('foo=bar', 'T_TERM T_ASSIGNMENT T_TERM');
        $this->assertTokensFor("foo:'bar'", 'T_TERM T_ASSIGNMENT T_SINGLE_LQUOTE T_STRING T_SINGLE_RQUOTE');
    }

    /** @test */
    public function it_ignores_spaces()
    {
        $this->assertTokensFor(' foo = bar ', 'T_TERM T_ASSIGNMENT T_TERM');
    }

    /** @test */
    public function it_lexes_parenthesis()
    {
        $this->assertTokensFor('(foo:bar)', 'T_LPARENTHESIS T_TERM T_ASSIGNMENT T_TERM T_RPARENTHESIS');
    }

    /** @test */
    public function it_lexes_comparaisons()
    {
        $this->assertTokensFor('foo<bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo<=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo>bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo>=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assertTokensFor('foo<"bar"', 'T_TERM T_COMPARATOR T_DOUBLE_LQUOTE T_STRING T_DOUBLE_RQUOTE');
    }

    /** @test */
    public function it_lexes_boolean_operator()
    {
        $this->assertTokensFor('foo and bar', 'T_TERM T_AND T_TERM');
        $this->assertTokensFor('foo or bar', 'T_TERM T_OR T_TERM');
        $this->assertTokensFor('foo and not bar', 'T_TERM T_AND T_NOT T_TERM');
    }

    /** @test */
    public function it_lexes_in_operator_with_commas()
    {
        $this->assertTokensFor(
            'foo in (a,b,c)',
            'T_TERM T_IN T_LPARENTHESIS T_TERM T_COMMA T_TERM T_COMMA T_TERM T_RPARENTHESIS'
        );
    }

    /** @test */
    public function it_lexes_complex_queries()
    {
        $this->assertTokensFor(
            'foo12bar.x.y?z and (foo:1 or bar> 3)',
            'T_TERM T_AND T_LPARENTHESIS T_TERM T_ASSIGNMENT T_TERM T_OR T_TERM T_COMPARATOR T_TERM T_RPARENTHESIS'
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
        $this->assertTokensFor('and)', 'T_AND T_RPARENTHESIS');
        $this->assertTokensFor('or)', 'T_OR T_RPARENTHESIS');
        $this->assertTokensFor('not)', 'T_NOT T_RPARENTHESIS');
        $this->assertTokensFor('in)', 'T_IN T_RPARENTHESIS');
    }

    public function assertTokensFor($input, $expectedTokens)
    {
        $tokens = $this->lex($input)->map->token->all();
        array_pop($tokens); // Ignore EOF token.
        $this->assertEquals($expectedTokens, implode(' ', $tokens));
    }
}
