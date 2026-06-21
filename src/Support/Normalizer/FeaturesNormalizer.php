<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Normalizer;

use Illuminate\Support\Facades\Config;
use Victormgomes\QueryParams\Enums\AssociatedIndex;

class FeaturesNormalizer
{
    public static function filter(array $data): array
    {
        $features = Config::get('query-params.features', [
            'filters' => true,
            'sorts' => true,
            'includes' => true,
            'fields' => true,
            'page' => true,
        ]);

        if (! ($features['includes'] ?? true)) {
            unset($data[AssociatedIndex::INCLUDES->value]);
        }
        if (! ($features['sorts'] ?? true)) {
            unset($data[AssociatedIndex::SORTS->value]);
        }
        if (! ($features['fields'] ?? true)) {
            unset($data[AssociatedIndex::FIELDS->value]);
        }
        if (! ($features['filters'] ?? true)) {
            unset($data[AssociatedIndex::FILTERS->value]);
        }
        if (! ($features['page'] ?? true)) {
            unset($data[AssociatedIndex::PAGE->value]);
        }

        return $data;
    }
}
