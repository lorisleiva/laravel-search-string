<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\TestCase;

class LexerTest extends TestCase
{
    /** @test */
    public function it_lexes_quoted_strings()
    {
        $this->assetTokensFor('"Hello world"', 'T_DOUBLE_LQUOTE T_STRING T_DOUBLE_RQUOTE');
        $this->assetTokensFor("'Hello world'", 'T_SINGLE_LQUOTE T_STRING T_SINGLE_RQUOTE');
    }

    /** @test */
    public function it_lexes_assignments()
    {
        $this->assetTokensFor('foo:bar', 'T_TERM T_ASSIGNMENT T_TERM');
        $this->assetTokensFor('foo=bar', 'T_TERM T_ASSIGNMENT T_TERM');
        $this->assetTokensFor("foo:'bar'", 'T_TERM T_ASSIGNMENT T_SINGLE_LQUOTE T_STRING T_SINGLE_RQUOTE');
    }

    /** @test */
    public function it_ignores_spaces()
    {
        $this->assetTokensFor(' foo = bar ', 'T_TERM T_ASSIGNMENT T_TERM');
    }

    /** @test */
    public function it_lexes_parenthesis()
    {
        $this->assetTokensFor('(foo:bar)', 'T_LPARENTHESIS T_TERM T_ASSIGNMENT T_TERM T_RPARENTHESIS');
    }

    /** @test */
    public function it_lexes_comparaisons()
    {
        $this->assetTokensFor('foo<bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo<=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo>bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo>=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo<"bar"', 'T_TERM T_COMPARATOR T_DOUBLE_LQUOTE T_STRING T_DOUBLE_RQUOTE');
    }

    /** @test */
    public function it_lexes_boolean_operator()
    {
        $this->assetTokensFor('foo and bar', 'T_TERM T_AND T_TERM');
        $this->assetTokensFor('foo or bar', 'T_TERM T_OR T_TERM');
        $this->assetTokensFor('foo and not bar', 'T_TERM T_AND T_NOT T_TERM');
    }

    /** @test */
    public function it_lexes_in_operator_with_commas()
    {
        $this->assetTokensFor(
            'foo in (a,b,c)',
            'T_TERM T_IN T_LPARENTHESIS T_TERM T_COMMA T_TERM T_COMMA T_TERM T_RPARENTHESIS'
        );
    }

    /** @test */
    public function it_lexes_complex_queries()
    {
        $this->assetTokensFor(
            'foo12bar.x.y?z and (foo:1 or bar> 3)',
            'T_TERM T_AND T_LPARENTHESIS T_TERM T_ASSIGNMENT T_TERM T_OR T_TERM T_COMPARATOR T_TERM T_RPARENTHESIS'
        );
    }

    /** @test */
    public function it_lexes_greedily_on_terms()
    {
        $this->assetTokensFor('and', 'T_AND');
        $this->assetTokensFor('andora', 'T_TERM');
        $this->assetTokensFor('or', 'T_OR');
        $this->assetTokensFor('oracle', 'T_TERM');
        $this->assetTokensFor('not', 'T_NOT');
        $this->assetTokensFor('notice', 'T_TERM');
    }

    /** @test */
    public function terminating_keywords_operators_stay_keywords()
    {
        $this->assetTokensFor('and', 'T_AND');
        $this->assetTokensFor('or', 'T_OR');
        $this->assetTokensFor('not', 'T_NOT');
        $this->assetTokensFor('in', 'T_IN');
        $this->assetTokensFor('and)', 'T_AND T_RPARENTHESIS');
        $this->assetTokensFor('or)', 'T_OR T_RPARENTHESIS');
        $this->assetTokensFor('not)', 'T_NOT T_RPARENTHESIS');
        $this->assetTokensFor('in)', 'T_IN T_RPARENTHESIS');
    }

    public function assetTokensFor($input, $expectedTokens)
    {
        $tokens = $this->lex($input)->map->token->all();
        array_pop($tokens); // Ignore EOF token.
        $this->assertEquals($expectedTokens, implode(' ', $tokens));
    }
}
