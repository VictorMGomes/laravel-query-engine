<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\ModelInfo;
use Illuminate\Database\Eloquent\ModelInspector;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use Victormgomes\QueryParams\Attributes\QueryOptions;
use Victormgomes\QueryParams\Enums\AssociatedIndex;
use Victormgomes\QueryParams\Support\Resource\FieldGenerator;
use Victormgomes\QueryParams\Support\Resource\FilterGenerator;
use Victormgomes\QueryParams\Support\Resource\IncludeGenerator;
use Victormgomes\QueryParams\Support\Resource\PaginationGenerator;
use Victormgomes\QueryParams\Support\Resource\SortGenerator;

class Resource
{
    private static array $cache = [];

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public static function generate(string $modelFQCN, $connection = null): array
    {
        if (isset(self::$cache[$modelFQCN])) {
            return self::$cache[$modelFQCN];
        }

        $inspector = new ModelInspector(app());
        $modelInstance = new $modelFQCN;
        $visible = $modelInstance->getVisible();
        $hidden = $modelInstance->getHidden();

        // Use configured metadata connection if none provided
        $connection ??= Config::get('query-params.metadata_connection');

        /** @var ModelInfo $modelInfo */
        $modelInfo = $inspector->inspect($modelFQCN, $connection);

        $attributes = [];
        foreach ($modelInfo->attributes as $attribute) {
            $name = $attribute['name'];

            // 1. If $visible is defined, only include fields in $visible
            if (! empty($visible) && ! in_array($name, $visible, true)) {
                continue;
            }

            // 2. Always respect $hidden
            if (in_array($name, $hidden, true) || ($attribute['hidden'] ?? false) === true) {
                continue;
            }

            $attributes[] = $attribute;
        }

        $relationMap = RelationMapper::getMap($modelFQCN);

        $reflection = new ReflectionClass($modelFQCN);
        $attributesList = $reflection->getAttributes(QueryOptions::class);
        $modelConfig = ! empty($attributesList) ? $attributesList[0]->newInstance() : null;

        $features = Config::get('query-params.features', [
            'filters' => true,
            'sorts' => true,
            'includes' => true,
            'fields' => true,
            'page' => true,
        ]);

        if ($modelConfig) {
            if ($modelConfig->filters !== null) {
                $features['filters'] = $modelConfig->filters;
            }
            if ($modelConfig->sorts !== null) {
                $features['sorts'] = $modelConfig->sorts;
            }
            if ($modelConfig->includes !== null) {
                $features['includes'] = $modelConfig->includes;
            }
            if ($modelConfig->fields !== null) {
                $features['fields'] = $modelConfig->fields;
            }
            if ($modelConfig->page !== null) {
                $features['page'] = $modelConfig->page;
            }
        }

        $allowedFilters = $modelConfig ? $modelConfig->allowedFilters : null;
        $disabledFilters = $modelConfig ? ($modelConfig->disableFilters ?? []) : [];
        $allowedSorts = $modelConfig ? $modelConfig->allowedSorts : null;
        $disabledSorts = $modelConfig ? ($modelConfig->disableSorts ?? []) : [];
        $allowedIncludes = $modelConfig ? $modelConfig->allowedIncludes : null;
        $disabledIncludes = $modelConfig ? ($modelConfig->disableIncludes ?? []) : [];
        $allowedFields = $modelConfig ? $modelConfig->allowedFields : null;
        $disabledFields = $modelConfig ? ($modelConfig->disableFields ?? []) : [];
        $allowedScopes = $modelConfig ? $modelConfig->allowedScopes : [];
        $allowedAggregations = $modelConfig ? $modelConfig->allowedAggregations : [];

        $availableColumns = array_column($attributes, 'name');
        $availableRelations = array_keys($relationMap);
        $allValidFields = array_merge($availableColumns, $availableRelations);

        self::validateConfigFields($modelFQCN, $allowedFilters ?? [], $allValidFields, 'filters (allowed)');
        self::validateConfigFields($modelFQCN, $disabledFilters, $allValidFields, 'filters (disabled)');
        self::validateConfigFields($modelFQCN, $allowedSorts ?? [], $allValidFields, 'sorts (allowed)');
        self::validateConfigFields($modelFQCN, $disabledSorts, $allValidFields, 'sorts (disabled)');
        self::validateConfigFields($modelFQCN, $allowedIncludes ?? [], $availableRelations, 'includes (allowed)');
        self::validateConfigFields($modelFQCN, $disabledIncludes, $availableRelations, 'includes (disabled)');
        self::validateConfigFields($modelFQCN, $allowedFields ?? [], $availableColumns, 'fields (allowed)');
        self::validateConfigFields($modelFQCN, $disabledFields, $availableColumns, 'fields (disabled)');

        return self::$cache[$modelFQCN] = [
            'filters' => ($features['filters'] ?? true) ? FilterGenerator::generate($attributes, $relationMap, $modelFQCN, $allowedFilters, $disabledFilters, $modelConfig?->allowedOperators, $modelConfig?->disableOperators, $allowedScopes) : [],
            'sorts' => ($features['sorts'] ?? true) ? SortGenerator::generate($attributes, $relationMap, $allowedSorts, $disabledSorts) : [],
            'pagination' => ($features['page'] ?? true) ? PaginationGenerator::generate() : [],
            'fields' => ($features['fields'] ?? true) ? FieldGenerator::generate($attributes, $relationMap, $allowedFields, $disabledFields, $modelFQCN, $allowedAggregations) : [],
            'includes' => ($features['includes'] ?? true) ? IncludeGenerator::generate($relationMap, $allowedIncludes, $disabledIncludes) : [],
        ];
    }

    private static function validateConfigFields(string $modelFQCN, array $configFields, array $validFields, string $featureName): void
    {
        if (empty($configFields)) {
            return;
        }

        $invalid = array_diff($configFields, $validFields);
        if (! empty($invalid)) {
            throw new \LogicException(sprintf(
                'Configuration error in Model [%s]. You tried to configure %s for fields/relations that do not exist: %s',
                $modelFQCN,
                $featureName,
                implode(', ', $invalid)
            ));
        }
    }

    /**
     * Returns a rich metadata structure specifically designed for frontend dynamic filter builders.
     * Fully covers all 5 operations: Filters, Sorts, Fields, Includes, and Pagination.
     */
    public static function getQueryGuide(string $modelFQCN): array
    {
        $resource = self::generate($modelFQCN);

        return [
            'model' => class_basename($modelFQCN),
            'available_filters' => $resource['filters'],
            'available_sorts' => $resource['sorts'],
            'available_fields' => array_keys($resource['fields']),
            'available_includes' => $resource['includes'],
            'pagination_settings' => $resource['pagination'],
            'syntax' => [
                'filters' => '{"field":{"operator":"value"}}',
                'sorts' => '{"field":"direction"}',
                'fields' => '["field1","field2"]',
                'includes' => '["relation1","relation2"]',
                'page' => '{"number":1,"limit":10}',
            ],
        ];
    }

    /**
     * Returns a definitive, cleaned-up metadata structure for frontend services.
     * Hides redundant aliases and internal mapping details.
     */
    public static function getFilterSchema(string $modelFQCN): array
    {
        $resource = self::generate($modelFQCN);

        return [
            'model' => class_basename($modelFQCN),
            'filters' => self::cleanUpFilters($resource['filters']),
            'sorts' => array_keys($resource['filters']),
            'fields' => array_keys($resource['fields']),
            'includes' => self::cleanUpIncludes($resource['includes']),
            'pagination' => $resource['pagination'],
        ];
    }

    private static function cleanUpFilters(array $rawFilters): array
    {
        $filters = [];
        foreach ($rawFilters as $name => $data) {
            if ($data['type'] === 'relation_id') {
                if (isset($data['is_alias']) && $data['is_alias'] === false && Str::snake($name) !== $name) {
                    continue;
                }

                if (isset($data['is_alias']) && $data['is_alias'] === true) {
                    $fancyName = Str::snake($data['maps_to']);
                    if ($name !== $fancyName && isset($rawFilters[$fancyName])) {
                        continue;
                    }
                }
            }

            $filters[$name] = [
                'type' => $data['type'],
                'operations' => $data['operations'],
            ];
        }

        return $filters;
    }

    private static function cleanUpIncludes(array $rawIncludes): array
    {
        $includes = [];
        foreach ($rawIncludes as $name => $data) {
            if (Str::snake($name) !== $name) {
                continue;
            }

            $fancyName = Str::snake($data['maps_to']);
            if ($name !== $fancyName && isset($rawIncludes[$fancyName])) {
                continue;
            }

            $includes[$name] = [
                'related' => $data['related'],
                'type' => $data['type'],
            ];
        }

        return $includes;
    }
}
