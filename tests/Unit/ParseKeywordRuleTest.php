<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\SearchStringManager;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class ParseKeywordRuleTest extends TestCase
{
    /** @test */
    function it_keep_rules_that_are_defined_has_regex_patterns()
    {
        $rule = [
            'key' => '/^foobar$/',
            'operator' => '~[=><]{1,3}~',
            'value' => '~\d+~',
        ];

        $this->assertEquals($rule, (array) $this->parseRule($rule));
    }

    /** @test */
    function it_wraps_non_regex_patterns_into_regex_delimiters()
    {
        $rule = (array) $this->parseRule([
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
        $rule = (array) $this->parseRule([
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
            'key' => '/^fallback_to_keyword_name$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], (array) $this->parseRule(null));

        $this->assertEquals([
            'key' => '/^fallback_to_keyword_name$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], (array) $this->parseRule([]));
    }

    /** @test */
    function it_parses_string_rules_as_the_key_of_the_rule()
    {
        $rule = 'foobar';
        $this->assertEquals([
            'key' => '/^foobar$/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], (array) $this->parseRule($rule));

        $rule = '/^\w{1,10}\?/';
        $this->assertEquals([
            'key' => '/^\w{1,10}\?/',
            'operator' => '/.*/',
            'value' => '/.*/',
        ], (array) $this->parseRule($rule));
    }

    public function parseRule($rule)
    {
        return $this->getSearchStringManager()
            ->ParseKeywordRule($rule, 'fallback_to_keyword_name');
    }
}