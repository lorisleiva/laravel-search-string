<?php

namespace Lorisleiva\LaravelSearchString\Tests\Feature;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\ExtractKeywordVisitor;

class ExtractLimitKeywordTest extends TestCase
{
    /** @test */
    function it_sets_the_limit_of_the_builder()
    {
        $builder = $this->getBuilderFor('limit:10');
        $this->assertEquals(10, $builder->getQuery()->limit);
    }

    /** @test */
    function it_throws_an_exception_if_the_limit_is_not_an_integer()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('limit:foobar');
    }

    /** @test */
    function it_throws_an_exception_if_the_limit_is_an_array()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('limit:10,foo,23');
    }

    /** @test */
    function it_uses_only_the_last_limit_that_matches()
    {
        $builder = $this->getBuilderFor('limit:10 limit:20 limit:30');
        $this->assertEquals(30, $builder->getQuery()->limit);
    }

    public function getBuilderFor($input)
    {
        return $this->getBuilderAfterExtracting('limit', $input);
    }
}