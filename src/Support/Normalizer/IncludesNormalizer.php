<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Normalizer;

use Victormgomes\QueryParams\Support\RelationMapper;

class IncludesNormalizer
{
    public static function normalize(mixed $includesRaw, ?string $modelFQCN): array
    {
        $includes = (array) $includesRaw;
        $parsed = [];

        foreach ($includes as $key => $value) {
            if (is_string($key)) {
                $relation = $modelFQCN
                    ? (RelationMapper::resolveRelation($modelFQCN, $key) ?? $key)
                    : $key;

                if (is_array($value)) {
                    if (array_is_list($value)) {
                        $value = ['fields' => $value];
                    }

                    $parsed[$relation] = [
                        'fields' => (array) ($value['fields'] ?? []),
                    ];
                } else {
                    $parsed[$relation] = ['fields' => []];
                }
            } else {
                $include = trim((string) $value);

                if ($modelFQCN) {
                    $include = RelationMapper::resolveRelation($modelFQCN, $include) ?? $include;
                }

                $parsed[] = $include;
            }
        }

        return $parsed;
    }
}
