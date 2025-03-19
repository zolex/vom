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

use Zolex\VOM\Mapping as VOM;

#[VOM\Model]
class SerializedObject
{
    public string $filename;
    public string $tag;
    public string $description;

    public function __construct(
        #[VOM\Argument(serialized: true)]
        string $data,
    ) {
        $parts = explode(',', $data);
        $this->filename = array_shift($parts);
        foreach ($parts as $part) {
            [$key, $value] = explode(':', $part);
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }
    }

    #[VOM\Normalizer]
    public function __toString(): string
    {
        return $this->filename.',tag:'.$this->tag.',description:'.$this->description;
    }
}
