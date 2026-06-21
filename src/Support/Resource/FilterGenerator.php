<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Resource;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use ReflectionClass;
use Victormgomes\QueryParams\Enums\Operators;
use Victormgomes\QueryParams\Support\Types;

final class FilterGenerator
{
    private array $filters = [];
    private array $operatorTypes;

    public function __construct(
        private readonly array|Collection $attributes,
        private readonly array $relationMap,
        private readonly ?string $modelFQCN,
        private readonly ?array $allowedFilters,
        private readonly array $disabledFilters,
        private readonly array $allowedOperators,
        private readonly array $allowedScopes
    ) {
        $this->operatorTypes = Types::getOperatorTypes();
    }

    public static function generate(
        array|Collection $attributes,
        array $relationMap = [],
        ?string $modelFQCN = null,
        ?array $allowedFilters = null,
        array $disabledFilters = [],
        ?array $modelAllowedOperators = null,
        ?array $modelDisableOperators = null,
        array $allowedScopes = []
    ): array {
        $allowedOperators = $modelAllowedOperators ?? Config::get('query-params.allowed_operators', Operators::values());
        if (! empty($modelDisableOperators)) {
            $allowedOperators = array_values(array_diff($allowedOperators, $modelDisableOperators));
        }
        $operators = array_intersect(Operators::values(), $allowedOperators);

        $generator = new self(
            $attributes,
            $relationMap,
            $modelFQCN,
            $allowedFilters,
            $disabledFilters,
            $operators,
            $allowedScopes
        );

        return $generator->build();
    }

    private function build(): array
    {
        $this->generateStandardFilters();
        $this->appendRelationFilters();
        $this->appendExistenceFilters();
        $this->appendSoftDeletesFilters();
        $this->appendScopeFilters();

        return $this->filters;
    }

    private function isFilterAllowed(string $name): bool
    {
        if ($this->allowedFilters !== null && ! in_array($name, $this->allowedFilters, true)) {
            return false;
        }
        return ! in_array($name, $this->disabledFilters, true);
    }

    private function generateStandardFilters(): void
    {
        foreach ($this->attributes as $attribute) {
            $name = $attribute['name'];
            if (! $this->isFilterAllowed($name)) {
                continue;
            }

            $columnType = Types::resolveType($attribute['type'] ?? 'string');
            $allowedOps = [];

            foreach ($this->allowedOperators as $operator) {
                $allowedTypes = $this->operatorTypes[$operator] ?? [];
                if (in_array($columnType, $allowedTypes, true)) {
                    $allowedOps[] = $operator;
                }
            }

            if (! empty($allowedOps)) {
                $this->filters[$name] = [
                    'type' => $columnType,
                    'operations' => $allowedOps,
                ];
            }
        }
    }

    private function appendRelationFilters(): void
    {
        foreach ($this->relationMap as $name => $data) {
            if (! $this->isFilterAllowed($name)) {
                continue;
            }

            if (isset($data['foreign_key']) && ! isset($this->filters[$name])) {
                $relationOps = array_intersect(
                    [Operators::EQ->value, Operators::NE->value, Operators::IN->value, Operators::NIN->value],
                    $this->allowedOperators
                );

                if (! empty($relationOps)) {
                    $this->filters[$name] = [
                        'type' => 'relation_id',
                        'operations' => array_values($relationOps),
                        'is_alias' => $data['is_alias'] ?? false,
                        'maps_to' => $data['foreign_key'],
                    ];
                }
            }
        }
    }

    private function appendExistenceFilters(): void
    {
        foreach ($this->relationMap as $name => $data) {
            if (! $this->isFilterAllowed($name)) {
                continue;
            }

            $relationOps = array_intersect([Operators::EXISTS->value, Operators::NOTEXISTS->value], $this->allowedOperators);
            if (! empty($relationOps)) {
                if (isset($this->filters[$name])) {
                    $this->filters[$name]['operations'] = array_values(array_unique(array_merge(
                        $this->filters[$name]['operations'],
                        $relationOps
                    )));
                } else {
                    $this->filters[$name] = [
                        'type' => 'relation',
                        'operations' => array_values($relationOps),
                        'is_alias' => $data['is_alias'] ?? false,
                        'maps_to' => $data['real_name'],
                    ];
                }
            }
        }
    }

    private function appendSoftDeletesFilters(): void
    {
        if ($this->modelFQCN && in_array(SoftDeletes::class, class_uses_recursive($this->modelFQCN), true)) {
            $booleanOps = array_intersect([Operators::EQ->value], $this->allowedOperators);
            if (! empty($booleanOps)) {
                $ops = array_values($booleanOps);
                $this->filters['with_deleted'] = ['type' => 'boolean', 'operations' => $ops];
                $this->filters['only_deleted'] = ['type' => 'boolean', 'operations' => $ops];
            }
        }
    }

    private function appendScopeFilters(): void
    {
        if (! empty($this->allowedScopes) && $this->modelFQCN) {
            $reflection = new ReflectionClass($this->modelFQCN);
            foreach ($this->allowedScopes as $scope) {
                $methodName = 'scope'.ucfirst($scope);
                if ($reflection->hasMethod($methodName)) {
                    $method = $reflection->getMethod($methodName);
                    $hasParams = $method->getNumberOfParameters() > 1;

                    $this->filters[$scope] = [
                        'type' => $hasParams ? 'string' : 'boolean',
                        'operations' => [Operators::EQ->value],
                        'is_scope' => true,
                    ];
                }
            }
        }
    }
}
