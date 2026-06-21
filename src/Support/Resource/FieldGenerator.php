<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Resource;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class FieldGenerator
{
    public static function generate(array|Collection $attributes, array $relationMap = [], ?array $allowedFields = null, array $disabledFields = [], ?string $modelFQCN = null, array $allowedAggregations = []): array
    {
        $fields = self::generateStandardFields($attributes, $allowedFields, $disabledFields);

        if ($modelFQCN) {
            self::appendAccessorFields($fields, $modelFQCN, $allowedFields, $disabledFields);
        }

        self::appendAggregationFields($fields, $relationMap, $allowedAggregations, $allowedFields, $disabledFields);

        return $fields;
    }

    private static function generateStandardFields(array|Collection $attributes, ?array $allowedFields, array $disabledFields): array
    {
        $fields = [];
        foreach ($attributes as $attribute) {
            $name = $attribute['name'];
            if ($allowedFields !== null && ! in_array($name, $allowedFields, true)) {
                continue;
            }
            if (in_array($name, $disabledFields, true)) {
                continue;
            }

            $fields[$name] = [
                'operations' => ['add'],
                'is_accessor' => false,
            ];
        }

        return $fields;
    }

    private static function appendAccessorFields(array &$fields, string $modelFQCN, ?array $allowedFields, array $disabledFields): void
    {
        $accessors = self::getAccessors($modelFQCN);
        foreach ($accessors as $accessor) {
            if ($allowedFields !== null && ! in_array($accessor, $allowedFields, true)) {
                continue;
            }
            if (in_array($accessor, $disabledFields, true)) {
                continue;
            }

            $fields[$accessor] = [
                'operations' => ['add'],
                'is_accessor' => true,
            ];
        }
    }

    private static function appendAggregationFields(array &$fields, array $relationMap, array $allowedAggregations, ?array $allowedFields, array $disabledFields): void
    {
        foreach ($allowedAggregations as $agg) {
            if ($allowedFields !== null && ! in_array($agg, $allowedFields, true)) {
                continue;
            }
            if (in_array($agg, $disabledFields, true)) {
                continue;
            }

            if (preg_match('/^(.+)_(count|exists)$/', $agg, $matches)) {
                $relation = $matches[1];
                if (isset($relationMap[$relation])) {
                    $fields[$agg] = [
                        'operations' => ['add'],
                        'is_aggregation' => true,
                        'agg_type' => $matches[2],
                        'relation' => $relation,
                    ];
                }
            } elseif (preg_match('/^(.+)_(sum|avg|min|max)_(.+)$/', $agg, $matches)) {
                $relation = $matches[1];
                if (isset($relationMap[$relation])) {
                    $fields[$agg] = [
                        'operations' => ['add'],
                        'is_aggregation' => true,
                        'agg_type' => $matches[2],
                        'relation' => $relation,
                        'column' => $matches[3],
                    ];
                }
            }
        }
    }

    private static function getAccessors(string $modelFQCN): array
    {
        $reflection = new ReflectionClass($modelFQCN);
        $accessors = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            $name = $method->getName();

            // Old syntax: getFirstNameAttribute()
            if (str_starts_with($name, 'get') && str_ends_with($name, 'Attribute') && $name !== 'getAttribute') {
                $accessors[] = Str::snake(substr($name, 3, -9));

                continue;
            }

            // New syntax PHP 8+: firstName(): Attribute
            $returnType = $method->getReturnType();
            if ($returnType instanceof ReflectionNamedType && $returnType->getName() === Attribute::class) {
                $accessors[] = Str::snake($name);
            }
        }

        $instance = new $modelFQCN;
        $appends = $instance->getAppends();

        return array_values(array_unique(array_merge($accessors, $appends)));
    }
}
