<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Resource;

use Victormgomes\QueryParams\Enums\AssociatedIndex;

final class PaginationGenerator
{
    public static function generate(): array
    {
        return [
            'keys' => [AssociatedIndex::NUMBER->value, AssociatedIndex::LIMIT->value, 'cursor'],
            'defaults' => [
                'limit' => 10,
                'max_limit' => 100,
            ],
        ];
    }
}
