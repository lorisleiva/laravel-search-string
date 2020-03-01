<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyModel extends Model
{
    use SearchString;

    protected $casts = [
        'paid' => 'boolean',
    ];

    protected $searchStringColumns = [
        'name' => [ 'searchable' => true ],
        'price',
        'description' => [ 'searchable' => true ],
        'paid',         // Automatically marked as boolean.
        'boolean_variable' => [ 'boolean' => true ],
        'created_at',   // Automatically marked as date and boolean.
    ];

    protected $searchStringKeywords = [
        'order_by' => 'sort',
        'select' => 'fields',
        'limit' => 'limit',
        'offset' => 'from',
    ];
}