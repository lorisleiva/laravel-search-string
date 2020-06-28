<?php

namespace Lorisleiva\LaravelSearchString\Tests\Feature;

use Lorisleiva\LaravelSearchString\Exceptions\InvalidSearchStringException;
use Lorisleiva\LaravelSearchString\Tests\TestCase;

class ErrorHandlingStrategiesTest extends TestCase
{
    /** @test */
    public function exceptions_startegy_throws_on_lexer_error()
    {
        $this->setStrategy('exceptions');
        $this->expectException(InvalidSearchStringException::class);
        $this->build('Hello "');
    }

    /** @test */
    public function exceptions_startegy_throws_on_parser_error()
    {
        $this->setStrategy('exceptions');
        $this->expectException(InvalidSearchStringException::class);
        $this->build('parser error in in');
    }

    /** @test */
    public function exceptions_startegy_throws_on_unmatched_key()
    {
        $this->setStrategy('exceptions');
        $this->expectException(InvalidSearchStringException::class);

        $model = $this->getModelWithColumns(['foo']);

        $this->build('bar:1', $model);
    }

    /** @test */
    public function all_results_strategy_returns_an_unmodified_query_builder()
    {
        $this->setStrategy('all-results');
        $this->assertSqlFor('parser error in in', 'select * from dummy_models');
    }

    /** @test */
    public function no_results_strategy_returns_a_query_builder_with_a_limit_of_zero()
    {
        $this->setStrategy('no-results');
        $this->assertSqlFor('parser error in in', 'select * from dummy_models where 1 = 0');
    }

    public function setStrategy($strategy)
    {
        config()->set('search-string.fail', $strategy);
    }
}
