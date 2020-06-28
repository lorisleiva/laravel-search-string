<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyComment extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'title' => [ 'searchable' => true ],
        'body' => [ 'searchable' => true ],
        'created_at' => 'date',
    ];
}
