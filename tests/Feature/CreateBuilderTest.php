<?php

namespace Lorisleiva\LaravelSearchString\Tests\Unit;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class CreateBuilderTest extends TestCase
{
    /** @test */
    public function it_does_not_filter_anything_by_default()
    {
        $this->assertSqlFor('', 'select * from dummy_models');
        $this->assertSqlFor('()', 'select * from dummy_models');
        $this->assertSqlFor(' () ', 'select * from dummy_models');
        $this->assertSqlFor('((()))', 'select * from dummy_models');
    }

    /** @test */
    public function it_filters_the_columns_to_select()
    {
        $this->assertSqlFor('fields:name', 'select name from dummy_models');
        $this->assertSqlFor('fields:name,price', 'select name, price from dummy_models');
        $this->assertSqlFor('fields:price,name', 'select name, price from dummy_models');

        $this->assertSqlFor('not fields:name', 
            'select price, description, paid, boolean_variable, created_at from dummy_models'
        );

        $this->assertSqlFor('not fields:name, price', 
            'select description, paid, boolean_variable, created_at from dummy_models'
        );
    }

    /** @test */
    public function it_orders_the_results()
    {
        $this->assertSqlFor('sort:name', 'select * from dummy_models order by name asc');
        $this->assertSqlFor('sort:name,price', 'select * from dummy_models order by name asc, price asc');
        $this->assertSqlFor('sort:-price,name', 'select * from dummy_models order by price desc, name asc');
        $this->assertSqlFor('sort:-price,-name', 'select * from dummy_models order by price desc, name desc');
    }

    /** @test */
    public function not_before_sort_does_not_affect_the_order_of_sort()
    {
        $this->assertSqlFor('not sort:name', 'select * from dummy_models order by name asc');
        $this->assertSqlFor('not sort:-price,name', 'select * from dummy_models order by price desc, name asc');
    }

    /** @test */
    public function it_limits_and_offsets_the_results()
    {
        $this->assertSqlFor('limit:10', 'select * from dummy_models limit 10');
        $this->assertSqlFor('from:10', 'select * from dummy_models offset 10');
        $this->assertSqlFor('limit:10 from:10', 'select * from dummy_models limit 10 offset 10');
        $this->assertSqlFor('from:10 limit:10', 'select * from dummy_models limit 10 offset 10');
    }

    /** @test */
    public function it_throws_an_exception_if_limit_is_not_a_positive_integer()
    {
        config()->set('search-string.fail', 'exceptions');
        $this->expectException(InvalidSearchStringException::class);
        $this->getSearchStringManager()->createBuilder('limit:-1');
    }

    /** @test */
    public function it_throws_an_exception_if_offset_is_not_a_positive_integer()
    {
        config()->set('search-string.fail', 'exceptions');
        $this->expectException(InvalidSearchStringException::class);
        $this->getSearchStringManager()->createBuilder('from:"foo bar"');
    }

    /** @test */
    public function not_before_limit_and_offset_does_not_affect_the_results()
    {
        $this->assertSqlFor('not limit:10', 'select * from dummy_models limit 10');
        $this->assertSqlFor('not from:10', 'select * from dummy_models offset 10');
    }

    /** @test */
    public function it_filters_basic_queries()
    {
        // Assignments
        $this->assertWhereSqlFor('name:John', "name = 'John'");
        $this->assertWhereSqlFor('name=John', "name = 'John'");
        $this->assertWhereSqlFor('name="John doe"', "name = 'John doe'");
        $this->assertWhereSqlFor('not name:John', "name != 'John'");

        // Booleans
        $this->assertWhereSqlFor('boolean_variable', "boolean_variable = true");
        $this->assertWhereSqlFor('paid', "paid = true");
        $this->assertWhereSqlFor('not paid', "paid = false");

        // Comparaisons
        $this->assertWhereSqlFor('price>0', "price > 0");
        $this->assertWhereSqlFor('price>=0', "price >= 0");
        $this->assertWhereSqlFor('price<0', "price < 0");
        $this->assertWhereSqlFor('price<=0', "price <= 0");
        $this->assertWhereSqlFor('price>0.55', "price > 0.55");

        // Null in capital treated as null value
        $this->assertWhereSqlFor('name:NULL', "name is null");
        $this->assertWhereSqlFor('not name:NULL', "name is not null");
    }

    /** @test */
    public function it_parses_date_filters_using_carbon()
    {
        $this->assertWhereSqlFor('created_at = "2018-05-17 10:30:00"', "created_at = 2018-05-17 10:30:00");

        $this->assertWhereSqlFor('created_at = 2018-05-17', 
            "(created_at >= 2018-05-17 00:00:00 and created_at <= 2018-05-17 23:59:59)"
        );

        $this->assertWhereSqlFor('not created_at = 2018-05-17', 
            "(created_at < 2018-05-17 00:00:00 and created_at > 2018-05-17 23:59:59)"
        );

        $this->assertWhereSqlFor('created_at = "May 17 2018"', 
            "(created_at >= 2018-05-17 00:00:00 and created_at <= 2018-05-17 23:59:59)"
        );

        $tomorrowStart = now()->addDay(1)->startOfDay();
        $tomorrowEnd = now()->addDay(1)->endOfDay();

        $this->assertWhereSqlFor('created_at = tomorrow', 
            "(created_at >= $tomorrowStart and created_at <= $tomorrowEnd)"
        );

        $this->assertWhereSqlFor('created_at < tomorrow', "created_at < $tomorrowStart");
        $this->assertWhereSqlFor('created_at <= tomorrow', "created_at <= $tomorrowEnd");
        $this->assertWhereSqlFor('created_at > tomorrow', "created_at > $tomorrowEnd");
        $this->assertWhereSqlFor('created_at >= tomorrow', "created_at >= $tomorrowStart");
    }

    /** @test */
    public function it_can_use_dates_as_boolean_by_filtering_on_null_values()
    {
        $this->assertWhereSqlFor('created_at', "created_at is not null");
        $this->assertWhereSqlFor('not created_at', "created_at is null");
    }

    /** @test */
    public function it_filters_in_array_queries()
    {
        $this->assertWhereSqlFor('name in (John, Jane)', "name in ('John', 'Jane')");
        $this->assertWhereSqlFor('not name in (John, Jane)', "name not in ('John', 'Jane')");
        $this->assertWhereSqlFor('name in (John)', "name in ('John')");
        $this->assertWhereSqlFor('not name in (John)', "name not in ('John')");

        // Array assignment treated as whereIn
        $this->assertWhereSqlFor('name:John,Jane', "name in ('John', 'Jane')");
        $this->assertWhereSqlFor('not name:John,Jane', "name not in ('John', 'Jane')");
        
        // Array comparisons treated as their first item
        $this->assertWhereSqlFor('name>John,Jane', "name > 'John'");
    }

    /** @test */
    public function it_filters_search_terms_and_strings()
    {
        $this->assertWhereSqlFor('John', 
            "(name like '%John%' or description like '%John%')"
        );

        $this->assertWhereSqlFor('"John Doe"', 
            "(name like '%John Doe%' or description like '%John Doe%')"
        );

        $this->assertWhereSqlFor('not John', 
            "(name not like '%John%' and description not like '%John%')"
        );
    }

    /** @test */
    public function it_creates_nested_where_clauses_using_or_and_operators()
    {
        $this->assertWhereSqlFor('name:John and price>0', "(name = 'John' and price > 0)");
        $this->assertWhereSqlFor('name:John or name:Jane', "(name = 'John' or name = 'Jane')");
        $this->assertWhereSqlFor('name:1 and name:2 or name:3', "((name = 1 and name = 2) or name = 3)");
        $this->assertWhereSqlFor('name:1 and (name:2 or name:3)', "(name = 1 and (name = 2 or name = 3))");
    }

    /** @test */
    public function it_creates_complex_queries()
    {
        $this->assertSqlFor(
            'name in (John,Jane) or description=Employee and created_at < 2018-05-18 limit:3 or Banana from:1', 
            "select * from dummy_models "
            . "where (name in ('John', 'Jane') "
            . "or (description = 'Employee' and created_at < 2018-05-18 00:00:00) "
            . "or (name like '%Banana%' or description like '%Banana%')) "
            . "limit 3 offset 1"
        );
    }
}