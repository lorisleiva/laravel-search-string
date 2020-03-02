<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyChild extends Model
{
    use SearchString;

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $searchStringColumns = [
        'active',
        'title' => ['searchable' => true],
    ];

    protected $searchStringRelations = [
        'user' => 'author',
    ];

    public function user()
    {
        return $this->belongsTo(DummyGrandChild::class);
    }
}
