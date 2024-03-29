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

namespace Zolex\VOM\Test\Fixtures;

use Symfony\Component\Serializer\Attribute\Groups;
use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    #[Groups('data')]
    private array $data = [];

    private array $moreData = [];

    #[VOM\Denormalizer]
    public function denormalizeData(
        /* @type int $id */
        #[VOM\Argument]
        int|string|null $id,
        #[VOM\Argument]
        string $name,
    ): void {
        $this->data = [
            'id' => $id,
            'name' => $name,
        ];
    }

    #[VOM\Normalizer]
    public function normalizeData(): array
    {
        return $this->data;
    }

    #[VOM\Denormalizer]
    #[Groups(['more'])]
    public function setMoreData(
        /* @type int $id */
        #[VOM\Argument('[data2_id]')]
        int $id,
        #[VOM\Argument('[data2_name]')]
        string $name,
    ): void {
        $this->moreData = [
            'data2_id' => $id,
            'data2_name' => $name,
        ];
    }

    #[Groups(['more'])]
    #[VOM\Normalizer]
    public function getMoreData(): array
    {
        return $this->moreData;
    }

    #[Groups(['good'])]
    #[VOM\Normalizer(accessor: '[good_string]')]
    public function getGoodString(): string
    {
        return 'string';
    }

    #[Groups(['bad'])]
    #[VOM\Normalizer]
    public function getBadString(): string
    {
        return 'string';
    }
}
