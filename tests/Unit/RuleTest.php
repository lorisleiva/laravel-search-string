<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Options\Rule;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class RuleTest extends TestCase
{
    /** @test */
    function it_keep_rules_that_are_defined_has_regex_patterns()
    {
        $rule = [
            'key' => '/^foobar$/',
            'operator' => '~[=><]{1,3}~',
            'value' => '~\d+~',
        ];

        $this->assertEquals($rule, $this->parseRule($rule));
    }

    /** @test */
    function it_wraps_non_regex_patterns_into_regex_delimiters()
    {
        $rule = $this->parseRule([
            'key' => 'key',
            'operator' => 'operator',
            'value' => 'value',
        ]);

        $this->assertEquals([
            'key' => '/^key$/',
            'operator' => '/^operator$/',
            'value' => '/^value$/',
        ], $rule);
    }

    /** @test */
    function it_preg_quote_non_regex_patterns()
    {
        $rule = $this->parseRule([
            'key' => '/ke(y',
            'operator' => '^\d$',
            'value' => '.*\w(value',
        ]);

        $this->assertEquals([
            'key' => '/^\/ke\(y$/',
            'operator' => '/^\^\\\d\$$/',
            'value' => '/^\.\*\\\w\(value$/',
        ], $rule);
    }

    /** @test */
    function it_provides_fallback_values_when_patterns_are_missing()
    {
        $this->assertEquals([
            'key' => '/^fallback_to_column_name$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], $this->parseRule(null, 'fallback_to_column_name'));

        $this->assertEquals([
            'key' => '/^fallback_to_column_name$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], $this->parseRule([], 'fallback_to_column_name'));
    }

    /** @test */
    function it_parses_string_rules_as_the_key_of_the_rule()
    {
        $rule = 'foobar';
        $this->assertEquals([
            'key' => '/^foobar$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], $this->parseRule($rule));

        $rule = '/^\w{1,10}\?/';
        $this->assertEquals([
            'key' => '/^\w{1,10}\?/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], $this->parseRule($rule));
    }

    /** @test */
    function it_treats_null_values_as_if_the_key_pair_wasnt_provided()
    {
        $rule = [
            'key' => null,
            'operator' => null,
            'value' => null,
        ];

        $this->assertEquals([
            'key' => '/^fallback_column$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], $this->parseRule($rule, 'fallback_column'));
    }

    public function parseRule($rule, $column = 'column')
    {
        $rule = (array) new class($column, $rule) extends Rule {};
        unset($rule['column']);
        return $rule;
    }
}