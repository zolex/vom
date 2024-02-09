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

namespace Zolex\VOM\Metadata;

use Symfony\Component\PropertyInfo\Type;
use Zolex\VOM\Mapping\AbstractProperty;
use Zolex\VOM\Serializer\Normalizer\BooleanNormalizer;
use Zolex\VOM\Serializer\Normalizer\CommonFlagNormalizer;

class PropertyMetadata implements GroupsAwareMetadataInterface
{
    use ContextAwareMetadataTrait;

    private readonly mixed $defaultValue;

    public function __construct(
        private readonly string $name,
        /* @var array|Type[] */
        private string $type,
        private ?string $arrayAccessType,
        private readonly AbstractProperty $attribute,
        private readonly array $groups = ['default'],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getField(): ?string
    {
        return $this->attribute->getField() ?? (($accessor = $this->getAccessor()) ? $accessor : null);
    }

    public function getArrayAccessType(): ?string
    {
        return $this->arrayAccessType;
    }

    public function getType(): ?string
    {
        if ($this->isFlag()) {
            return CommonFlagNormalizer::TYPE;
        }

        if ($this->isBool()) {
            return BooleanNormalizer::TYPE;
        }

        return $this->type;
    }

    public function getBuiltinType(): ?string
    {
        foreach ($this->types as $type) {
            if ($type = $type->getBuiltinType()) {
                return $type;
            }
        }

        return null;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function isBool(): bool
    {
        return 'bool' === $this->type;
    }

    public function isFlag(): bool
    {
        return $this->attribute->isFlag();
    }

    public function getAccessor(array $context = []): string|false
    {
        $accessor = $this->attribute->getAccessor();
        if (false === $effectiveAccessor = (true === $accessor ? '['.$this->name.']' : $accessor)) {
            return false;
        }

        return $effectiveAccessor;
    }

    public function getAliases(): array
    {
        return $this->attribute->getAliases();
    }

    public function getAlias(string $name): ?string
    {
        return $this->attribute->getAlias($name);
    }

    public function isNested(): bool
    {
        return $this->attribute->isNested();
    }

    public function isCollection(): bool
    {
        foreach ($this->types as $type) {
            if ($type->isCollection()) {
                return true;
            }
        }

        return false;
    }

    public function isRoot(): bool
    {
        return $this->attribute->isRoot();
    }

    public function getTrueValue(): bool|string|int|null
    {
        return $this->attribute->getTrueValue();
    }

    public function getFalseValue(): bool|string|int|null
    {
        return $this->attribute->getFalseValue();
    }

    public function getDefaultOrder(): ?string
    {
        return $this->attribute->getDefaultOrder();
    }

    public function getDateTimeFormat(): string
    {
        return $this->attribute->getDateTimeFormat() ?? \DateTimeInterface::RFC3339_EXTENDED;
    }

    public function getDefaultValue(): mixed
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(mixed $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    public function hasDefaultValue(): bool
    {
        return isset($this->defaultValue);
    }
}
