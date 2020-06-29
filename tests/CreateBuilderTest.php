<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\Concerns\DumpsSql;

class CreateBuilderTest extends TestCase
{
    use DumpsSql;

    /** @test */
    public function it_does_not_filter_anything_by_default()
    {
        $this->assertSqlEquals('', 'select * from dummy_models');
        $this->assertSqlEquals('()', 'select * from dummy_models');
        $this->assertSqlEquals(' () ', 'select * from dummy_models');
        $this->assertSqlEquals('((()))', 'select * from dummy_models');
    }

    /** @test */
    public function it_filters_the_columns_to_select()
    {
        $this->assertSqlEquals('fields:name', 'select name from dummy_models');
        $this->assertSqlEquals('fields:name,price', 'select name, price from dummy_models');
        $this->assertSqlEquals('fields:price,name', 'select name, price from dummy_models');

        $this->assertSqlEquals('not fields:name',
            'select price, description, paid, boolean_variable, created_at from dummy_models'
        );

        $this->assertSqlEquals('not fields:name, price',
            'select description, paid, boolean_variable, created_at from dummy_models'
        );
    }

    /** @test */
    public function it_orders_the_results()
    {
        $this->assertSqlEquals('sort:name', 'select * from dummy_models order by name asc');
        $this->assertSqlEquals('sort:name,price', 'select * from dummy_models order by name asc, price asc');
        $this->assertSqlEquals('sort:-price,name', 'select * from dummy_models order by price desc, name asc');
        $this->assertSqlEquals('sort:-price,-name', 'select * from dummy_models order by price desc, name desc');
    }

    /** @test */
    public function not_before_sort_does_not_affect_the_order_of_sort()
    {
        $this->assertSqlEquals('not sort:name', 'select * from dummy_models order by name asc');
        $this->assertSqlEquals('not sort:-price,name', 'select * from dummy_models order by price desc, name asc');
    }

    /** @test */
    public function it_limits_and_offsets_the_results()
    {
        $this->assertSqlEquals('limit:10', 'select * from dummy_models limit 10');
        $this->assertSqlEquals('from:10', 'select * from dummy_models offset 10');
        $this->assertSqlEquals('limit:10 from:10', 'select * from dummy_models limit 10 offset 10');
        $this->assertSqlEquals('from:10 limit:10', 'select * from dummy_models limit 10 offset 10');
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
        $this->assertSqlEquals('not limit:10', 'select * from dummy_models limit 10');
        $this->assertSqlEquals('not from:10', 'select * from dummy_models offset 10');
    }

    /** @test */
    public function it_filters_basic_queries()
    {
        // Assignments.
        $this->assertWhereSqlEquals('name:John', "name = 'John'");
        $this->assertWhereSqlEquals('name=John', "name = 'John'");
        $this->assertWhereSqlEquals('name="John doe"', "name = 'John doe'");
        $this->assertWhereSqlEquals('not name:John', "name != 'John'");

        // Booleans.
        $this->assertWhereSqlEquals('boolean_variable', "boolean_variable = true");
        $this->assertWhereSqlEquals('paid', "paid = true");
        $this->assertWhereSqlEquals('not paid', "paid = false");

        // Comparisons.
        $this->assertWhereSqlEquals('price>0', "price > 0");
        $this->assertWhereSqlEquals('price>=0', "price >= 0");
        $this->assertWhereSqlEquals('price<0', "price < 0");
        $this->assertWhereSqlEquals('price<=0', "price <= 0");
        $this->assertWhereSqlEquals('price>0.55', "price > 0.55");

        // Null in capital treated as null value.
        $this->assertWhereSqlEquals('name:NULL', "name is null");
        $this->assertWhereSqlEquals('not name:NULL', "name is not null");
    }

    /** @test */
    public function it_parses_date_filters_using_carbon()
    {
        $this->assertWhereSqlEquals('created_at = "2018-05-17 10:30:00"', "created_at = 2018-05-17 10:30:00");

        $this->assertWhereSqlEquals('created_at = 2018-05-17',
            "(created_at >= 2018-05-17 00:00:00 and created_at <= 2018-05-17 23:59:59)"
        );

        $this->assertWhereSqlEquals('not created_at = 2018-05-17',
            "(created_at < 2018-05-17 00:00:00 and created_at > 2018-05-17 23:59:59)"
        );

        $this->assertWhereSqlEquals('created_at = "May 17 2018"',
            "(created_at >= 2018-05-17 00:00:00 and created_at <= 2018-05-17 23:59:59)"
        );

        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd = now()->addDay()->endOfDay();

        $this->assertWhereSqlEquals('created_at = tomorrow',
            "(created_at >= $tomorrowStart and created_at <= $tomorrowEnd)"
        );

        $this->assertWhereSqlEquals('created_at < tomorrow', "created_at < $tomorrowStart");
        $this->assertWhereSqlEquals('created_at <= tomorrow', "created_at <= $tomorrowEnd");
        $this->assertWhereSqlEquals('created_at > tomorrow', "created_at > $tomorrowEnd");
        $this->assertWhereSqlEquals('created_at >= tomorrow', "created_at >= $tomorrowStart");
    }

    /** @test */
    public function it_can_use_dates_as_boolean_by_filtering_on_null_values()
    {
        $this->assertWhereSqlEquals('created_at', "created_at is not null");
        $this->assertWhereSqlEquals('not created_at', "created_at is null");
    }

    /** @test */
    public function it_filters_in_array_queries()
    {
        $this->assertWhereSqlEquals('name in (John, Jane)', "name in ('John', 'Jane')");
        $this->assertWhereSqlEquals('not name in (John, Jane)', "name not in ('John', 'Jane')");
        $this->assertWhereSqlEquals('name in (John)', "name in ('John')");
        $this->assertWhereSqlEquals('not name in (John)', "name not in ('John')");

        // Array assignment treated as whereIn
        $this->assertWhereSqlEquals('name:John,Jane', "name in ('John', 'Jane')");
        $this->assertWhereSqlEquals('not name:John,Jane', "name not in ('John', 'Jane')");
    }

    /** @test */
    public function it_filters_search_terms_and_strings()
    {
        $this->assertWhereSqlEquals('John',
            "(name like '%John%' or description like '%John%')"
        );

        $this->assertWhereSqlEquals('"John Doe"',
            "(name like '%John Doe%' or description like '%John Doe%')"
        );

        $this->assertWhereSqlEquals('not John',
            "(name not like '%John%' and description not like '%John%')"
        );
    }

    /** @test */
    public function it_creates_nested_where_clauses_using_or_and_operators()
    {
        $this->assertWhereSqlEquals('name:John and price>0', "(name = 'John' and price > 0)");
        $this->assertWhereSqlEquals('name:John or name:Jane', "(name = 'John' or name = 'Jane')");
        $this->assertWhereSqlEquals('name:1 and name:2 or name:3', "((name = 1 and name = 2) or name = 3)");
        $this->assertWhereSqlEquals('name:1 and (name:2 or name:3)', "(name = 1 and (name = 2 or name = 3))");
    }

    /** @test */
    public function it_creates_complex_queries()
    {
        $this->assertSqlEquals(
            'name in (John,Jane) or description=Employee and created_at < 2018-05-18 limit:3 or Banana from:1',
            "select * from dummy_models "
            . "where (name in ('John', 'Jane') "
            . "or (description = 'Employee' and created_at < 2018-05-18 00:00:00) "
            . "or (name like '%Banana%' or description like '%Banana%')) "
            . "limit 3 offset 1"
        );
    }

    /** @test */
    public function it_uses_the_real_column_name_when_using_an_alias()
    {
        $model = $this->getModelWithColumns([
            'zipcode' => 'postcode',
            'created_at' => ['key' => 'created', 'date' => true, 'boolean' => true],
            'activated' => ['key' => 'active', 'boolean' => true],
        ]);

        $this->assertWhereSqlEquals('postcode:1028', "zipcode = 1028", $model);
        $this->assertWhereSqlEquals('postcode>10', "zipcode > 10", $model);
        $this->assertWhereSqlEquals('not postcode in (1000, 1002)', "zipcode not in ('1000', '1002')", $model);
        $this->assertWhereSqlEquals('created>2019-01-01', "created_at > 2019-01-01 23:59:59", $model);
        $this->assertWhereSqlEquals('created', "created_at is not null", $model);
        $this->assertWhereSqlEquals('not created', "created_at is null", $model);
        $this->assertWhereSqlEquals('active', "activated = true", $model);
        $this->assertWhereSqlEquals('not active', "activated = false", $model);
    }
}
