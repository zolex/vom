<?php

declare(strict_types=1);

namespace Zolex\VOM\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Model
{
    private array $searchable;

    public function __construct(
        private array $presets = [],
        array|string $searchable = [],
        private ?string $defaultTrueValue = null,
        private ?string $defaultFalseValue = null,
    ) {
        $this->searchable = is_string($searchable) ? [$searchable => 'GET'] : $searchable;
    }

    public function getSearchable(): array
    {
        return $this->searchable;
    }

    public function getPreset(string $name): ?array
    {
        return $this->presets[$name] ?? null;
    }

    public function getDefaultTrueValue(): ?string
    {
        return $this->defaultTrueValue;
    }

    public function getDefaultFalseValue(): ?string
    {
        return $this->defaultFalseValue;
    }
}
