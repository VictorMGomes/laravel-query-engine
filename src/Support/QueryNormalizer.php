<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Victormgomes\QueryParams\Contracts\FieldResolver;
use Victormgomes\QueryParams\Enums\AssociatedIndex;
use Victormgomes\QueryParams\Support\Normalizer\FeaturesNormalizer;
use Victormgomes\QueryParams\Support\Normalizer\FiltersNormalizer;
use Victormgomes\QueryParams\Support\Normalizer\IncludesNormalizer;
use Victormgomes\QueryParams\Support\Normalizer\SortsNormalizer;

class QueryNormalizer
{
    protected static \WeakMap $normalized;

    public static function normalize(FormRequest|Request $request, ?string $modelFQCN = null): void
    {
        self::$normalized ??= new \WeakMap;

        if (isset(self::$normalized[$request])) {
            return;
        }

        $data = self::decodeJsonValues($request->all());

        $data[AssociatedIndex::INCLUDES->value] = IncludesNormalizer::normalize($data[AssociatedIndex::INCLUDES->value] ?? [], $modelFQCN);
        $data[AssociatedIndex::SORTS->value] = SortsNormalizer::normalize($data[AssociatedIndex::SORTS->value] ?? [], $modelFQCN);
        $data[AssociatedIndex::FIELDS->value] = (array) ($data[AssociatedIndex::FIELDS->value] ?? []);
        $data[AssociatedIndex::FILTERS->value] = FiltersNormalizer::normalize($data[AssociatedIndex::FILTERS->value] ?? [], $modelFQCN);
        $data[AssociatedIndex::PAGE->value] = (array) ($data[AssociatedIndex::PAGE->value] ?? []);

        $data = FeaturesNormalizer::filter($data);

        $request->query->replace($data);
        $request->replace($data);

        self::$normalized[$request] = true;
    }

    public static function resolveDriver(): ?FieldResolver
    {
        /** @var class-string<FieldResolver>|null $driverClass */
        $driverClass = Config::get('query-params.drivers.default');

        return $driverClass ? new $driverClass : null;
    }

    private static function decodeJsonValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && isset($value[0]) && in_array($value[0], ['{', '['], true)) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$key] = $decoded;
                }
            }
        }

        return $data;
    }
}
