<?php

declare(strict_types=1);

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Mapping;

use Zolex\VOM\Metadata\ModelMetadata;

/**
 * @psalm-inheritors Property|Argument
 */
abstract class AbstractProperty
{
    public function __construct(
        /**
         * @var string|string[]|bool
         */
        private string|bool|array $accessor = true,
        private ?string $field = null,
        private bool $root = false,
        private array $aliases = [],
        private bool|string|int|null $trueValue = null,
        private bool|string|int|null $falseValue = null,
        private ?string $defaultOrder = null,
        private ?string $dateTimeFormat = null,
        private ?array $map = null,
        private bool $serialized = false,
        private ?string $extractor = null,
        private string $scenario = ModelMetadata::DEFAULT_SCENARIO,
        private int|array|null $relative = null,
    ) {
        $this->applyRelativePropertyAccessSyntax();
    }

    /**
     * Applies the custom property-access syntax prefix
     * e.g. `[..][..]` as an alternative to `relative: 2`.
     */
    private function applyRelativePropertyAccessSyntax(): void
    {
        if (\is_array($this->accessor)) {
            $this->relative = [];
            foreach ($this->accessor as $index => &$accessor) {
                if (!\is_string($accessor)) {
                    continue;
                }

                $relativeCount = $this->consumeRelativePrefix($accessor);

                if ($relativeCount > 0) {
                    $this->relative[$index] = ($this->relative[$index] ?? 0) + $relativeCount;
                }
            }

            return;
        }

        if (!\is_string($this->accessor)) {
            return;
        }

        $relativeCount = $this->consumeRelativePrefix($this->accessor);

        if ($relativeCount > 0) {
            $this->relative = ($this->relative ?? 0) + $relativeCount;
        }
    }

    /**
     * Removes all leading `[..]` from the accessor and returns how many were removed.
     */
    private function consumeRelativePrefix(string &$accessor): int
    {
        $count = 0;
        while (str_starts_with($accessor, '[..]')) {
            ++$count;
            $accessor = substr($accessor, 4);
        }

        return $count;
    }

    public function isRoot(): bool
    {
        return $this->root;
    }

    public function getAccessor(): string|array|bool
    {
        return $this->accessor;
    }

    public function hasAccessor(): bool
    {
        return (bool) $this->accessor;
    }

    public function getField(): ?string
    {
        return $this->field;
    }

    public function getAliases(): array
    {
        return $this->aliases;
    }

    public function getAlias(string $name): ?string
    {
        return $this->aliases[$name] ?? null;
    }

    public function getTrueValue(): bool|string|int|null
    {
        return $this->trueValue;
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->falseValue;
    }

    public function getDefaultOrder(): ?string
    {
        return $this->defaultOrder;
    }

    public function getDateTimeFormat(): ?string
    {
        return $this->dateTimeFormat;
    }

    public function hasMap(): bool
    {
        return null !== $this->map;
    }

    public function getMap(): ?array
    {
        return $this->map;
    }

    public function isSerialized(): bool
    {
        return $this->serialized;
    }

    public function getExtractor(): ?string
    {
        return $this->extractor;
    }

    public function getScenario(): string
    {
        return $this->scenario;
    }

    public function getRelative(): int|array|null
    {
        return $this->relative;
    }
}
