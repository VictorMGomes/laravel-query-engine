<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Resource;

class IncludeGenerator
{
    public static function generate(array $relationMap, ?array $allowedIncludes = null, array $disabledIncludes = []): array
    {
        $includes = [];
        foreach ($relationMap as $name => $data) {
            if ($allowedIncludes !== null && ! in_array($name, $allowedIncludes, true)) {
                continue;
            }
            if (in_array($name, $disabledIncludes, true)) {
                continue;
            }

            $includes[$name] = [
                'type' => $data['type'] ?? 'Relation',
                'related' => $data['related'] ?? '',
                'is_alias' => $data['is_alias'] ?? false,
                'maps_to' => $data['real_name'],
            ];
        }

        return $includes;
    }
}
