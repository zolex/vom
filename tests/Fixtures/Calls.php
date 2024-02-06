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

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class Calls
{
    private array $data = [];
    private array $moreData = [];

    public function setData(
        /* @type int $id */
        #[VOM\Property]
        int $id,
        #[VOM\Property]
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

    public function setMoreData(
        /* @type int $id */
        #[VOM\Property('data2_id')]
        int $id,
        #[VOM\Property('data2_name')]
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
