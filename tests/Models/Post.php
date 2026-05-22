<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Post extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }
}
