<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Normalizer;

use Illuminate\Support\Facades\Config;
use Victormgomes\QueryParams\Enums\Operators;
use Victormgomes\QueryParams\Support\RelationMapper;

class FiltersNormalizer
{
    public static function normalize(mixed $filtersRaw, ?string $modelFQCN): array
    {
        $filters = (array) $filtersRaw;

        if ($modelFQCN) {
            $mappedFilters = [];

            foreach ($filters as $field => $ops) {
                $mappedFilters[RelationMapper::resolveFilterField($modelFQCN, $field)] = $ops;
            }

            $filters = $mappedFilters;
        }

        $allowedOperators = Config::get('query-params.allowed_operators', Operators::values());

        foreach ($filters as $field => $value) {
            if (! is_array($value)) {
                $value = [Operators::EQ->value => $value];
            }

            $filteredOps = [];
            foreach ($value as $op => $val) {
                if (in_array($op, $allowedOperators, true)) {
                    $filteredOps[$op] = $val;
                }
            }

            if (empty($filteredOps)) {
                unset($filters[$field]);
            } else {
                $filters[$field] = $filteredOps;
            }
        }

        return $filters;
    }
}
