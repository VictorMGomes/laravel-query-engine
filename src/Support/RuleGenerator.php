<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support;

use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Victormgomes\QueryParams\Enums\AbstractType;
use Victormgomes\QueryParams\Enums\RuleType;

final class RuleGenerator
{
    /**
     * @param array<string, mixed> $resources
     * @return array<string, mixed>
     */
    public static function generate(array $resources): array
    {
        $filtersRules = self::generateFilters($resources);
        $sortsRules = self::generateSorts($resources);
        $fieldsRules = self::generateFields($resources);
        $includesRules = self::generateIncludes($resources);
        $pagesRules = self::generatePages($resources);

        return array_merge(
            $filtersRules,
            $sortsRules,
            $fieldsRules,
            $includesRules,
            $pagesRules
        );
    }

    /**
     * @param array<string, mixed> $resources
     * @return array<string, mixed>
     */
    private static function generateFilters(array $resources): array
    {
        $rules = [];
        $allowedFields = array_keys($resources['filters']);

        $rules['filters'] = ['sometimes', 'array' . ($allowedFields !== [] ? ':' . implode(',', $allowedFields) : '')];

        if ($allowedFields === []) {
            return $rules;
        }

        $operatorRules = Types::getOperatorRules();

        foreach ($resources['filters'] as $field => $config) {
            self::generateFilterRulesForField($rules, $field, $config, $operatorRules);
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $rules
     * @param string $field
     * @param array<string, mixed> $config
     * @param array<string, mixed> $operatorRules
     * @return void
     */
    private static function generateFilterRulesForField(array &$rules, string $field, array $config, array $operatorRules): void
    {
        $allowedOps = $config['operations'];
        $rules['filters.' . $field] = ['sometimes', 'array:' . implode(',', $allowedOps)];

        $dbType = $config['type'] ?? AbstractType::STRING;
        $dbTypeValue = $dbType instanceof AbstractType ? $dbType->value : (string) $dbType;

        foreach ($allowedOps as $operator) {
            $baseRule = $operatorRules[$operator];
            $rules['filters.' . $field . '.' . $operator] = RuleType::build($dbTypeValue, $baseRule);
        }
    }

    /**
     * @param array<string, mixed> $resources
     * @return array<string, mixed>
     */
    private static function generateSorts(array $resources): array
    {
        $rules = [];
        $allowedFields = array_keys($resources['sorts']);

        $rules['sorts'] = ['sometimes', 'array' . ($allowedFields !== [] ? ':' . implode(',', $allowedFields) : '')];

        if ($allowedFields === []) {
            return $rules;
        }

        foreach ($resources['sorts'] as $field => $config) {
            $rules['sorts.' . $field] = [RuleType::SOMETIMES, Rule::in($config['operations'])];
        }

        return $rules;
    }

    /**
     * @param array<string, mixed> $resources
     * @return array<string, mixed>
     */
    private static function generateFields(array $resources): array
    {
        $rules = [];
        $allowedFields = array_keys($resources['fields']);

        $rules['fields'] = ['sometimes', 'array'];

        if ($allowedFields === []) {
            return $rules;
        }

        $rules['fields.*'] = ['string', Rule::in($allowedFields)];

        return $rules;
    }

    /**
     * @param array<string, mixed> $resources
     * @return array<string, mixed>
     */
    private static function generateIncludes(array $resources): array
    {
        $rules = [];
        $allowedIncludes = array_keys($resources['includes']);

        $rules['includes'] = ['sometimes', 'array'];

        if ($allowedIncludes === []) {
            return $rules;
        }

        $rules['includes.*'] = ['string', Rule::in($allowedIncludes)];

        return $rules;
    }

    /**
     * @param array<string, mixed> $resources
     * @return array<string, mixed>
     */
    private static function generatePages(array $resources): array
    {
        $rules = [];
        $allowedPages = $resources['pagination']['keys'] ?? [];

        if ($allowedPages === []) {
            return $rules;
        }

        $rules['page'] = ['sometimes', 'array:' . implode(',', $allowedPages)];

        foreach ($allowedPages as $page) {
            $rules['page.' . $page] = self::getPageRule($page);
        }

        return $rules;
    }

    /**
     * @param string $page
     * @return array<int, string>
     */
    private static function getPageRule(string $page): array
    {
        if ($page === 'limit') {
            $maxLimit = Config::get('query-params.pagination.max_limit', 100);
            return ['sometimes', 'integer', 'min:1', "max:{$maxLimit}"];
        }

        if ($page === 'cursor') {
            return ['sometimes', 'string'];
        }

        return ['sometimes', 'integer', 'min:1'];
    }
}
