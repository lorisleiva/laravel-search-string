<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Lorisleiva\LaravelSearchString\Lexer\Lexer;
use Lorisleiva\LaravelSearchString\Parser\Parser;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return ['Lorisleiva\LaravelSearchString\ServiceProvider'];
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('search-string', include __DIR__ . '/../src/config.php');
    }

    public function lex($input)
    {
        return (new Lexer(config('search-string.token_map')))->lex($input);
    }

    public function parse($input)
    {
        return (new Parser)->parse($this->lex($input));
    }
}