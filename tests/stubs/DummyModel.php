<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;

class DummyModel extends Model
{
    public $searchStringOptions = [
        'columns' => [
            'visible' => ['name', 'price', 'description', 'paid', 'created_at'],
            'searchable' => ['name', 'description'],
            'boolean' => ['boolean_variable', 'paid'],
            'date' => ['created_at'],
        ],
        'keywords' => [
            'order_by' => 'sort',
            'select' => 'fields',
            'limit' => 'limit',
            'offset' => 'from',
        ],
    ];
}