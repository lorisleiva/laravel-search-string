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

    protected $searchStringRelations = [
        'comments',
        'categories' => [
            'key' => 'tags',
            'countable' => false,
        ],
        'pageViews' => [
            'key' => 'views',
            'queryable' => false,
        ],
    ];

    protected $searchStringKeywords = [
        'order_by' => 'sort',
        'select' => 'fields',
        'limit' => 'limit',
        'offset' => 'from',
    ];

    public function comments()
    {
        return $this->hasMany(DummyChild::class, 'post_id');
    }

    public function categories()
    {
        return $this->hasMany(DummyChild::class, 'post_id');
    }

    public function pageViews()
    {
        return $this->hasMany(DummyChild::class, 'post_id');
    }

    public function author()
    {
        return $this->belongsTo(DummyChild::class);
    }
}
