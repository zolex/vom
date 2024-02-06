<?php

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
    private array $data = [];
    private array $moreData = [];

    #[Groups('data')]
    #[VOM\Denormalizer]
    public function setData(
        /* @type int $id */
        #[VOM\Argument]
        int $id,
        #[VOM\Argument]
        string $name,
    ): void {
        $this->data = [
            'id' => $id,
            'name' => $name,
        ];
    }

    #[VOM\Normalizer]
    public function getData(): array
    {
        return $this->data;
    }

    #[Groups(['more'])]
    #[VOM\Denormalizer]
    public function setMoreData(
        /* @type int $id */
        #[VOM\Argument('data2_id')]
        int $id,
        #[VOM\Argument('data2_name')]
        string $name,
    ): void {
        $this->moreData = [
            'data2_id' => $id,
            'data2_name' => $name,
        ];
    }

    #[VOM\Normalizer]
    public function getMoreData(): array
    {
        return $this->moreData;
    }
}
