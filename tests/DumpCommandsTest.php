<?php

namespace Lorisleiva\LaravelSearchString\Tests;

use Illuminate\Support\Facades\Artisan;

class DumpCommandsTest extends TestCase
{
    /** @test */
    public function it_dumps_the_ast()
    {
        $this->assertEquals(
            <<<EOL
            AND
            >   name = A
            >   price > 10
            EOL,
            $this->ast('name: A price > 10')
        );

        $this->assertEquals(
            <<<EOL
            EXISTS(comments)
            >   EXISTS(author)
            >   >   name = John
            EOL,
            $this->ast('comments.author.name = John')
        );
    }

    public function ast(string $query)
    {
        return $this->query('ast', $query);
    }

    public function query(string $type, string $query)
    {
        Artisan::call(sprintf('search-string:%s /Lorisleiva/LaravelSearchString/Tests/Stubs/Product "%s"', $type, $query));

        return trim(Artisan::output(), "\n");
    }
}
