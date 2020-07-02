<?php

namespace Lorisleiva\LaravelSearchString\Console;

use Illuminate\Console\Command;

class DumpSqlCommand extends Command
{
    protected $signature = 'search-string:sql {model} {query*}';
    protected $description = 'Parses the given search string and dumps the resulting SQL';
}
