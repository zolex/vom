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

class NormalizerMetadata implements GroupsAwareMetadataInterface
{
    use ContextAwareMetadataTrait;

    private array $groups = [];

    public function __construct(
        private readonly string $method,
    ) {
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }
}
