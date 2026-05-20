<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface FieldResolver
{
    /**
     * Resolve and apply a filter to the query.
     */
    public function applyFilter(Builder $query, string $field, string $operator, mixed $value, string $locale): bool;

    /**
     * Resolve and apply a sort to the query.
     */
    public function applySort(Builder $query, string $field, string $direction, string $locale): bool;

    /**
     * Resolve and translate an item for output.
     */
    public function translateItem(Model $item, string $locale): mixed;
}
