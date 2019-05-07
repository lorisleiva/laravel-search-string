<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Options\Rule;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class RuleTest extends TestCase
{
    /** @test */
    public function it_keep_rules_that_are_defined_has_regex_patterns()
    {
        $rule = [
            'key' => '/^foobar$/',
            'operator' => '~[=><]{1,3}~',
            'value' => '~\d+~',
        ];

        $this->assertEquals(
            "[/^foobar$/ ~[=><]{1,3}~ ~\d+~]",
            $this->parseRule($rule)
        );
    }

    /** @test */
    public function it_wraps_non_regex_patterns_into_regex_delimiters()
    {
        $rule = $this->parseRule([
            'key' => 'key',
            'operator' => 'operator',
            'value' => 'value',
        ]);

        $this->assertEquals("[/^key$/ /^operator$/ /^value$/]", $rule);
    }

    /** @test */
    public function it_preg_quote_non_regex_patterns()
    {
        $rule = $this->parseRule([
            'key' => '/ke(y',
            'operator' => '^\d$',
            'value' => '.*\w(value',
        ]);

        $this->assertEquals(
            '[/^\/ke\(y$/ /^\^\\\\d\$$/ /^\.\*\\\w\(value$/]', 
            $rule
        );
    }

    /** @test */
    public function it_provides_fallback_values_when_patterns_are_missing()
    {
        $this->assertEquals(
            "[/^fallback_column$/ /.*/ /.*/]", 
            $this->parseRule(null, 'fallback_column')
        );

        $this->assertEquals(
            "[/^fallback_column$/ /.*/ /.*/]", 
            $this->parseRule([], 'fallback_column')
        );
    }

    /** @test */
    public function it_parses_string_rules_as_the_key_of_the_rule()
    {
        $this->assertEquals(
            "[/^foobar$/ /.*/ /.*/]", 
            $this->parseRule('foobar')
        );

        $this->assertEquals(
            "[/^\w{1,10}\?/ /.*/ /.*/]", 
            $this->parseRule('/^\w{1,10}\?/')
        );
    }

    /** @test */
    public function it_treats_null_values_as_if_the_key_pair_wasnt_provided()
    {
        $rule = [
            'key' => null,
            'operator' => null,
            'value' => null,
        ];

        $this->assertEquals(
            "[/^fallback_column$/ /.*/ /.*/]", 
            $this->parseRule($rule, 'fallback_column')
        );
    }

    public function parseRule($rule, $column = 'column')
    {
        return (string) new class($column, $rule) extends Rule {};
    }
}