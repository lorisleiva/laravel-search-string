<?php

namespace Lorisleiva\LaravelSearchString\Tests\Stubs;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Lorisleiva\LaravelSearchString\Concerns\SearchString;

class DummyCommentUser extends Pivot
{
    use SearchString;

    protected $searchStringColumns = [
        'comment' => ['relationship' => true],
        'user' => ['relationship' => true],
        'created_at' => 'date',
    ];

    public function comment()
    {
        return $this->belongsTo(DummyComment::class);
    }

    public function user()
    {
        return $this->belongsTo(DummyUser::class);
    }
}
