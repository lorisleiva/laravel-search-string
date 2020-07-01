<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyUser extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'name' => ['searchable' => true],
        'email' => ['searchable' => true],
        'created_at' => 'date',
    ];
}
