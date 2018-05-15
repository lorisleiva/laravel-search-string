<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Facade\SearchString;
use Lorisleiva\LaravelSearchString\Visitor\ExtractSortQueryVisitor;

class ExtractSortQueryVisitorTest extends TestCase
{
    /** @test */
    function it_sets_the_order_by_of_the_builder()
    {
        $builder = $this->getBuilderFor('sort:name');

        $this->assertEquals([
            [ 'column' => 'name', 'direction' => 'asc' ],
        ], $builder->getQuery()->orders);
    }

    /** @test */
    function it_sets_the_descending_order_when_preceded_by_a_minus()
    {
        $builder = $this->getBuilderFor('sort:-name');

        $this->assertEquals([
            [ 'column' => 'name', 'direction' => 'desc' ],
        ], $builder->getQuery()->orders);
    }

    /** @test */
    function it_can_set_multiple_order_by()
    {
        $builder = $this->getBuilderFor('sort:name,-price,created_at');

        $this->assertEquals([
            [ 'column' => 'name', 'direction' => 'asc' ],
            [ 'column' => 'price', 'direction' => 'desc' ],
            [ 'column' => 'created_at', 'direction' => 'asc' ],
        ], $builder->getQuery()->orders);
    }

    /** @test */
    function it_uses_only_the_last_query_that_matches()
    {
        $builder = $this->getBuilderFor('sort:name sort:-price sort:created_at');

        $this->assertEquals([
            [ 'column' => 'created_at', 'direction' => 'asc' ],
        ], $builder->getQuery()->orders);
    }

    public function getBuilderFor($input)
    {
        $builder = $this->getDummyBuilder();
        $visitor = new ExtractSortQueryVisitor($builder, '/^sort$/');
        SearchString::parse($input)->accept($visitor);
        return $builder;
    }
}