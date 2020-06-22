<?php

namespace Lorisleiva\LaravelSearchString\Compiler;

use Illuminate\Support\Enumerable;

interface CompilerInterface
{
    public function lex(string $input): Enumerable;
    public function parse(string $input): Enumerable;
    public function updateParser(): void;
}
