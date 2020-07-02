<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\Concerns\DumpsSql;
use Lorisleiva\LaravelSearchString\Tests\Stubs\Product;
use Lorisleiva\LaravelSearchString\Tests\Stubs\User;

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

            // Relationships existance.
            ['comments', "exists (select * from comments where products.id = comments.product_id)"],
            ['comments > 0', "exists (select * from comments where products.id = comments.product_id)"],
            ['comments >= 1', "exists (select * from comments where products.id = comments.product_id)"],
            ['not comments = 0', "exists (select * from comments where products.id = comments.product_id)"],

            // Relationships inexistance.
            ['not comments', "not exists (select * from comments where products.id = comments.product_id)"],
            ['not comments > 0', "not exists (select * from comments where products.id = comments.product_id)"],
            ['not comments >= 1', "not exists (select * from comments where products.id = comments.product_id)"],
            ['comments = 0', "not exists (select * from comments where products.id = comments.product_id)"],

            // Relationships count.
            ['comments = 10', "(select count(*) from comments where products.id = comments.product_id) = 10"],
            ['comments > 5', "(select count(*) from comments where products.id = comments.product_id) > 5"],
            ['not comments = 1', "(select count(*) from comments where products.id = comments.product_id) != 1"],
            ['not comments < 2', "(select count(*) from comments where products.id = comments.product_id) >= 2"],

            // Relationships nested terms.
            ['comments.author', "exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id))"],
            ['comments.title = "My comment"', "exists (select * from comments where products.id = comments.product_id and title = 'My comment')"],
            ['comments.author.name = John', "exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id and name = 'John'))"],
            ['comments.author.writtenComments', "exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id and exists (select * from comments where users.id = comments.user_id)))"],
            ['comments.favouritors > 10', "exists (select * from comments where products.id = comments.product_id and (select count(*) from users inner join comment_user on users.id = comment_user.user_id where comments.id = comment_user.comment_id) > 10)"],
            ['comments.favourites > 10', "exists (select * from comments where products.id = comments.product_id and (select count(*) from comment_user where comments.id = comment_user.comment_id) > 10)"],

            // Relationships nested terms negated.
            ['not comments.author', "not exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id))"],
            ['not comments.title = "My comment"', "not exists (select * from comments where products.id = comments.product_id and title = 'My comment')"],
            ['not comments.author.name = John', "not exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id and name = 'John'))"],
            ['not comments.author.writtenComments', "not exists (select * from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id and exists (select * from comments where users.id = comments.user_id)))"],
            ['not comments.favouritors > 10', "not exists (select * from comments where products.id = comments.product_id and (select count(*) from users inner join comment_user on users.id = comment_user.user_id where comments.id = comment_user.comment_id) > 10)"],
            ['not comments.favourites > 10', "not exists (select * from comments where products.id = comments.product_id and (select count(*) from comment_user where comments.id = comment_user.comment_id) > 10)"],

            // Nested relationships.
            ['comments: (title: Hi)', "exists (select * from comments where products.id = comments.product_id and title = 'Hi')"],
            ['comments: (not author)', "exists (select * from comments where products.id = comments.product_id and not exists (select * from users where comments.user_id = users.id))"],
            ['comments: (author.name: John or favourites > 5)', "exists (select * from comments where products.id = comments.product_id and (exists (select * from users where comments.user_id = users.id and name = 'John') or (select count(*) from comment_user where comments.id = comment_user.comment_id) > 5))"],
            ['comments: (favourites > 10) > 3', "(select count(*) from comments where products.id = comments.product_id and (select count(*) from comment_user where comments.id = comment_user.comment_id) > 10) > 3"],
            ['comments: ("This is great")', "exists (select * from comments where products.id = comments.product_id and (name like '%This is great%' or description like '%This is great%'))"],
            ['comments: (author: (name: "John Doe" age > 18)) > 3', "(select count(*) from comments where products.id = comments.product_id and exists (select * from users where comments.user_id = users.id and (name = 'John Doe' and age > 18))) > 3"],

            // Relationships & And/Or.
            ['name:A or comments: (title:B and title:C)', "(name = 'A' or exists (select * from comments where products.id = comments.product_id and (title = 'B' and title = 'C')))"],
            ['name:A or comments: (title:B or title:C)', "(name = 'A' or exists (select * from comments where products.id = comments.product_id and (title = 'B' or title = 'C')))"],
            ['name:A and not comments: (title:B or title:C)', "(name = 'A' and not exists (select * from comments where products.id = comments.product_id and (title = 'B' or title = 'C')))"],
            ['name:A (name:B or comments) or name:C and name:D', "((name = 'A' and (name = 'B' or exists (select * from comments where products.id = comments.product_id))) or (name = 'C' and name = 'D'))"],
            ['name:A not (name:B or comments) or name:C and name:D', "((name = 'A' and name != 'B' and not exists (select * from comments where products.id = comments.product_id)) or (name = 'C' and name = 'D'))"],
            ['name:A (name:B or not comments: (title:X and title:Y or not (author and not title:Z)))', "(name = 'A' and (name = 'B' or not exists (select * from comments where products.id = comments.product_id and ((title = 'X' and title = 'Y') or not exists (select * from users where comments.user_id = users.id) or title = 'Z'))))"],
        ];
    }

    public function expectInvalidSearchStringException()
    {
        return [
            'Limit should be a positive integer' => ['limit:-1'],
            'Offset should be a positive integer' => ['from:"foo bar"'],
            'Relationship expected count should be a positive integer' => ['comments = foo'],
            'Relationship expected count (from nested terms) should be a positive integer' => ['comments.author = bar'],
            'Relationship expected count (from nested relationship) should be a positive integer' => ['comments: (author: baz)'],
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
