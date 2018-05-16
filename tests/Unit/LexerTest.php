<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Tests\TestCase;

class LexerTest extends TestCase
{
    /** @test */
    function it_lexes_quoted_strings()
    {
        $this->assetTokensFor('"Hello world"', 'T_STRING');
    }

    /** @test */
    function it_lexes_assignments()
    {
        $this->assetTokensFor('foo:bar', 'T_TERM T_ASSIGN T_TERM');
        $this->assetTokensFor('foo=bar', 'T_TERM T_ASSIGN T_TERM');
        $this->assetTokensFor('foo:"bar"', 'T_TERM T_ASSIGN T_STRING');
    }

    /** @test */
    function it_lexes_spaces_and_parenthesis()
    {
        $this->assetTokensFor(' foo = bar ', 'T_SPACE T_TERM T_SPACE T_ASSIGN T_SPACE T_TERM T_SPACE');
        $this->assetTokensFor('(foo:bar)', 'T_LPARENT T_TERM T_ASSIGN T_TERM T_RPARENT');
    }

    /** @test */
    function it_lexes_comparaisons()
    {
        $this->assetTokensFor('foo<bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo<=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo>bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo>=bar', 'T_TERM T_COMPARATOR T_TERM');
        $this->assetTokensFor('foo<"bar"', 'T_TERM T_COMPARATOR T_STRING');
    }

    /** @test */
    function it_lexes_boolean_operator()
    {
        $this->assetTokensFor('foo and bar', 'T_TERM T_SPACE T_AND T_SPACE T_TERM');
        $this->assetTokensFor('foo or bar', 'T_TERM T_SPACE T_OR T_SPACE T_TERM');
        $this->assetTokensFor('foo and not bar', 'T_TERM T_SPACE T_AND T_SPACE T_NOT T_SPACE T_TERM');
    }

    /** @test */
    function it_lexes_in_operator_with_commas()
    {
        $this->assetTokensFor(
            'foo in (a,b,c)', 
            'T_TERM T_SPACE T_IN T_SPACE T_LPARENT T_TERM T_LIST_SEPARATOR T_TERM T_LIST_SEPARATOR T_TERM T_RPARENT'
        );
    }

    /** @test */
    function it_lexes_complex_queries()
    {
        $this->assetTokensFor(
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
    function it_lexes_greedily_on_terms()
    {
        $this->assetTokensFor('and', 'T_AND');
        $this->assetTokensFor('andora', 'T_TERM');
        $this->assetTokensFor('or', 'T_OR');
        $this->assetTokensFor('oracle', 'T_TERM');
        $this->assetTokensFor('not', 'T_NOT');
        $this->assetTokensFor('notice', 'T_TERM');
    }

    /** @test */
    function terminating_keywords_operators_stay_keywords()
    {
        $this->assetTokensFor('and', 'T_AND');
        $this->assetTokensFor('or', 'T_OR');
        $this->assetTokensFor('not', 'T_NOT');
        $this->assetTokensFor('in', 'T_IN');
        $this->assetTokensFor('and)', 'T_AND T_RPARENT');
        $this->assetTokensFor('or)', 'T_OR T_RPARENT');
        $this->assetTokensFor('not)', 'T_NOT T_RPARENT');
        $this->assetTokensFor('in)', 'T_IN T_RPARENT');
    }

    public function assetTokensFor($input, $expectedTokens)
    {
        $tokens = $this->lex($input)->map->type->implode(' ');
        $this->assertEquals($expectedTokens, $tokens);
    }
}