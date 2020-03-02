<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyGrandChild extends Model
{
    use SearchString;

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $searchStringColumns = [
        'active',
        'title',
    ];

    protected $searchStringRelations = [
        'profiles',
    ];

    public function profiles()
    {
        return $this->hasMany(static::class, 'user_id');
    }
}
