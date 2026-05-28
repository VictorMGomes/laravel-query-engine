<?php

declare(strict_types=1);

use Victormgomes\QueryParams\Support\RuleGenerator;

it('generates basic rules when resources are empty', function () {
    $resources = [
        'filters' => [],
        'sorts' => [],
        'fields' => [],
        'includes' => [],
    ];

    $rules = RuleGenerator::generate($resources);

    expect($rules)->toHaveKey('filters', ['sometimes', 'array'])
        ->and($rules)->toHaveKey('sorts', ['sometimes', 'array'])
        ->and($rules)->toHaveKey('fields', ['sometimes', 'array'])
        ->and($rules)->toHaveKey('includes', ['sometimes', 'array']);
});

it('generates specific array rules when resources have items', function () {
    $resources = [
        'filters' => ['name' => ['operations' => ['eq'], 'type' => 'string']],
        'sorts' => ['created_at' => ['operations' => ['asc', 'desc']]],
        'fields' => ['id', 'name'],
        'includes' => ['author'],
    ];

    $rules = RuleGenerator::generate($resources);

    expect($rules['filters'])->toBe(['sometimes', 'array:name'])
        ->and($rules['sorts'])->toBe(['sometimes', 'array:created_at'])
        ->and($rules['fields'])->toBe(['sometimes', 'array'])
        ->and($rules['includes'])->toBe(['sometimes', 'array']);
});
