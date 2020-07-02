<?php

namespace Lorisleiva\LaravelSearchString\Console;

use Illuminate\Console\Command;

class DumpResultCommand extends Command
{
    protected $signature = 'search-string:get {model} {query*}';
    protected $description = 'Parses the given search string and displays the result';
}
