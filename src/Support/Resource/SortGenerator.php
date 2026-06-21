<?php

declare(strict_types=1);

namespace Victormgomes\QueryParams\Support\Resource;

use Illuminate\Support\Collection;

final class SortGenerator
{
    private array $sorts = [];

    public function __construct(
        private readonly array|Collection $attributes,
        private readonly array $relationMap,
        private readonly ?array $allowedSorts,
        private readonly array $disabledSorts
    ) {}

    /**
     * @param  array<string, mixed>  $relationMap
     * @param  array<int, string>|null  $allowedSorts
     * @param  array<int, string>  $disabledSorts
     * @return array<string, mixed>
     */
    public static function generate(
        array|Collection $attributes,
        array $relationMap = [],
        ?array $allowedSorts = null,
        array $disabledSorts = []
    ): array {
        $generator = new self(
            $attributes,
            $relationMap,
            $allowedSorts,
            $disabledSorts
        );

        return $generator->build();
    }

    /**
     * @return array<string, mixed>
     */
    private function build(): array
    {
        $this->generateStandardSorts();
        $this->appendRelationSorts();

        return $this->sorts;
    }

    private function isSortAllowed(string $name): bool
    {
        if ($this->allowedSorts !== null && ! in_array($name, $this->allowedSorts, true)) {
            return false;
        }

        return ! in_array($name, $this->disabledSorts, true);
    }

    private function generateStandardSorts(): void
    {
        foreach ($this->attributes as $attribute) {
            $name = $attribute['name'];
            if ($this->isSortAllowed($name)) {
                $this->sorts[$name] = [
                    'operations' => ['asc', 'desc'],
                ];
            }
        }
    }

    private function appendRelationSorts(): void
    {
        foreach ($this->relationMap as $name => $data) {
            if (! $this->isSortAllowed($name)) {
                continue;
            }

            if (isset($data['foreign_key']) && ! isset($this->sorts[$name])) {
                $this->sorts[$name] = [
                    'operations' => ['asc', 'desc'],
                    'is_alias' => $data['is_alias'] ?? false,
                    'maps_to' => $data['foreign_key'],
                ];
            }
        }
    }
}
