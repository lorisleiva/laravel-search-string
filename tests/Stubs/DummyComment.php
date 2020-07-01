<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Model;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyComment extends Model
{
    use SearchString;

    protected $searchStringColumns = [
        'title' => ['searchable' => true],
        'body' => ['searchable' => true],
        'user' => [
            'key' => 'author',
            'relationship' => true,
        ],
        // 'favourites' => ['relationship' => true], // TODO: Uncomment to deal with circular relationships. Maybe just delay manager creation until we actually need it.
        'favouriteUsers' => ['relationship' => true],
        'created_at' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(DummyUser::class);
    }

    public function favourites()
    {
        return $this->belongsToMany(DummyCommentUser::class);
    }

    public function favouriteUsers()
    {
        return $this->belongsToMany(DummyUser::class);
    }
}
