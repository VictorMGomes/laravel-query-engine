<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Tests\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Victormgomes\QueryParams\Attributes\QueryOptions;

#[QueryOptions(
    allowedScopes: ['published', 'popular'],
    allowedAggregations: ['author_count']
)]
class Post extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class);
    }

    public function scopePublished($query): void
    {
        $query->where('is_published', true);
    }

    public function scopePopular($query, int $minViews): void
    {
        $query->where('views', '>=', $minViews);
    }

    public function virtualAttribute(): Attribute
    {
        return Attribute::make(get: fn () => 'virtual');
    }

    public function getVirtualLegacyAttribute(): string
    {
        return 'legacy';
    }
}
