<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\ExtractOffsetQueryVisitor;

class ResolveOffsetKeywordTest extends TestCase
{
    /** @test */
    function it_sets_the_offset_of_the_builder()
    {
        $builder = $this->getBuilderFor('from:10');
        $this->assertEquals(10, $builder->getQuery()->offset);
    }

    /** @test */
    function it_throws_an_exception_if_the_offset_is_not_an_integer()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('from:foobar');
    }

    /** @test */
    function it_throws_an_exception_if_the_offset_is_an_array()
    {
        $this->expectException(InvalidSearchStringException::class);
        $this->getBuilderFor('from:10,foo,23');
    }

    /** @test */
    function it_uses_only_the_last_offset_that_matches()
    {
        $builder = $this->getBuilderFor('from:10 from:20 from:30');
        $this->assertEquals(30, $builder->getQuery()->offset);
    }

    public function getBuilderFor($input)
    {
        return $this->getBuilderAfterExtracting('offset', $input);
    }
}