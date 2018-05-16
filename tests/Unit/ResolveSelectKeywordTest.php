<?php

namespace Lorisleiva\LaravelSearchString\Tests\Feature;

use Lorisleiva\LaravelSearchString\Tests\TestCase;
use Lorisleiva\LaravelSearchString\Visitor\ExtractSelectQueryVisitor;
use Lorisleiva\LaravelSearchString\Visitor\RemoveNotSymbolVisitor;

class ResolveSelectKeywordTest extends TestCase
{
    /** @test */
    function it_sets_the_columns_of_the_builder()
    {
        $builder = $this->getBuilderFor('fields:name');
        $this->assertEquals(['name'], $builder->getQuery()->columns);
    }

    /** @test */
    function it_excludes_columns_when_operator_is_negative()
    {
        $builder = $this->getBuilderFor('not fields:name');

        $this->assertEquals(
            ['price', 'description', 'paid', 'created_at'], 
            $builder->getQuery()->columns
        );
    }

    /** @test */
    function it_can_set_and_exclude_multiple_columns()
    {
        $builder = $this->getBuilderFor('fields:name,price,description');
        $this->assertEquals(['name', 'price', 'description'], $builder->getQuery()->columns);

        $builder = $this->getBuilderFor('not fields:name,price,description');
        $this->assertEquals(['paid', 'created_at'], $builder->getQuery()->columns);
    }

    /** @test */
    function it_uses_only_the_last_query_that_matches()
    {
        $builder = $this->getBuilderFor('fields:name fields:price fields:description');
        $this->assertEquals(['description'], $builder->getQuery()->columns);
    }

    public function getBuilderFor($input)
    {
        return $this->getBuilderAfterExtracting('select', $input);
    }
}