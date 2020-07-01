<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\Concerns\DumpsSql;

class CreateBuilderTest extends TestCase
{
    use DumpsSql;

    public function success()
    {
        return [
            // It does not filter anything by default.
            ['', 'select * from products'],
            ['()', 'select * from products'],
            [' () ', 'select * from products'],
            ['((()))', 'select * from products'],

            // Select.
            ['fields:name', 'select name from products'],
            ['fields:name,price', 'select name, price from products'],
            ['fields:price,name', 'select name, price from products'],
            ['not fields:name', 'select price, description, paid, boolean_variable, created_at from products'],
            ['not fields:name, price', 'select description, paid, boolean_variable, created_at from products'],

            // Order by.
            ['sort:name', 'select * from products order by name asc'],
            ['sort:name,price', 'select * from products order by name asc, price asc'],
            ['sort:-price,name', 'select * from products order by price desc, name asc'],
            ['sort:-price,-name', 'select * from products order by price desc, name desc'],

            // Sort
            ['not sort:name', 'select * from products order by name asc'],
            ['not sort:-price,name', 'select * from products order by price desc, name asc'],

            // Limit and offset.
            ['limit:10', 'select * from products limit 10'],
            ['from:10', 'select * from products offset 10'],
            ['limit:10 from:10', 'select * from products limit 10 offset 10'],
            ['from:10 limit:10', 'select * from products limit 10 offset 10'],

            // Not limit/offset has no effects.
            ['not limit:10', 'select * from products limit 10'],
            ['not from:10', 'select * from products offset 10'],

            // Complex examples.
            [
                'name in (John,Jane) or description=Employee and created_at < 2018-05-18 limit:3 or Banana from:1',
                "select * from products "
                . "where (name in ('John', 'Jane') "
                . "or (description = 'Employee' and created_at < 2018-05-18 00:00:00) "
                . "or (name like '%Banana%' or description like '%Banana%')) "
                . "limit 3 offset 1"
            ],
        ];
    }

    public function successWhereOnly()
    {
        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd = now()->addDay()->endOfDay();

        return [
            // Assignments.
            ['name:John', "name = 'John'"],
            ['name=John', "name = 'John'"],
            ['name="John doe"', "name = 'John doe'"],
            ['not name:John', "name != 'John'"],

            // Booleans.
            ['boolean_variable', "boolean_variable = true"],
            ['paid', "paid = true"],
            ['not paid', "paid = false"],

            // Comparisons.
            ['price>0', "price > 0"],
            ['price>=0', "price >= 0"],
            ['price<0', "price < 0"],
            ['price<=0', "price <= 0"],
            ['price>0.55', "price > 0.55"],

            // Null in capital treated as null value.
            ['name:NULL', "name is null"],
            ['not name:NULL', "name is not null"],

            // Dates.
            ['created_at = "2018-05-17 10:30:00"', "created_at = 2018-05-17 10:30:00"],
            ['created_at = 2018-05-17', "(created_at >= 2018-05-17 00:00:00 and created_at <= 2018-05-17 23:59:59)"],
            ['not created_at = 2018-05-17', "(created_at < 2018-05-17 00:00:00 and created_at > 2018-05-17 23:59:59)"],
            ['created_at = "May 17 2018"', "(created_at >= 2018-05-17 00:00:00 and created_at <= 2018-05-17 23:59:59)"],

            // Relative dates.
            ['created_at = tomorrow', "(created_at >= $tomorrowStart and created_at <= $tomorrowEnd)"],
            ['created_at < tomorrow', "created_at < $tomorrowStart"],
            ['created_at <= tomorrow', "created_at <= $tomorrowEnd"],
            ['created_at > tomorrow', "created_at > $tomorrowEnd"],
            ['created_at >= tomorrow', "created_at >= $tomorrowStart"],

            // Dates as booleans.
            ['created_at', "created_at is not null"],
            ['not created_at', "created_at is null"],

            // Lists.
            ['name in (John, Jane)', "name in ('John', 'Jane')"],
            ['not name in (John, Jane)', "name not in ('John', 'Jane')"],
            ['name in (John)', "name in ('John')"],
            ['not name in (John)', "name not in ('John')"],
            ['name:John,Jane', "name in ('John', 'Jane')"],
            ['not name:John,Jane', "name not in ('John', 'Jane')"],

            // Search.
            ['John', "(name like '%John%' or description like '%John%')"],
            ['"John Doe"', "(name like '%John Doe%' or description like '%John Doe%')"],
            ['not John', "(name not like '%John%' and description not like '%John%')"],

            // Nested And/Or where clauses.
            ['name:John and price>0', "(name = 'John' and price > 0)"],
            ['name:John or name:Jane', "(name = 'John' or name = 'Jane')"],
            ['name:1 and name:2 or name:3', "((name = 1 and name = 2) or name = 3)"],
            ['name:1 and (name:2 or name:3)', "(name = 1 and (name = 2 or name = 3))"],

            // Relationships.
            ['comments.title = "My comment"', "exists (select * from comments where products.id = comments.product_id and title = 'My comment')"],
            ['comments.author.name = John', "exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id and name = 'John'))"],
            // ['comments.author', "TODO"],
            // ['comments.author.tags', "TODO"],
            // ['not comments.author', "TODO"],
            // ['not comments.author = "John Doe"', "TODO"],

            // Nested relationships.
            // ['comments: (author: John or votes > 10)', "TODO"],
            // ['comments: (author: John) = 20', "TODO"],
            // ['comments: (author: John) <= 10', "TODO"],
            // ['comments: ("This is great")', "TODO"],
            // ['comments.author: (name: "John Doe" age > 18) > 3', "TODO"],
            // ['comments: (achievements: (Laravel) >= 2) > 10', "TODO"],
            // ['comments: (not achievements: (Laravel))', "TODO"],
            // ['not comments: (achievements: (Laravel))', "TODO"],
        ];
    }

    public function expectInvalidSearchStringException()
    {
        return [
            'Limit should be a positive integer' => ['limit:-1'],
            'Offset should be a positive integer' => ['from:"foo bar"'],
        ];
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

    /**
     * @test
     * @dataProvider success
     * @param string $input
     * @param string $expected
     */
    public function create_builder_success(string $input, string $expected)
    {
        $this->assertSqlEquals($input, $expected);
    }

    /**
     * @test
     * @dataProvider successWhereOnly
     * @param string $input
     * @param string $expected
     */
    public function create_builder_success_where_only(string $input, string $expected)
    {
        $this->assertWhereSqlEquals($input, $expected);
    }

    /**
     * @test
     * @dataProvider expectInvalidSearchStringException
     * @param string $input
     */
    public function create_builder_expect_exception(string $input)
    {
        config()->set('search-string.fail', 'exceptions');
        $this->expectException(InvalidSearchStringException::class);
        $this->build($input);
    }
}
