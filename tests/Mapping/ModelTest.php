<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zolex\VOM\Test\Mapping;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Mapping\Model;

class ModelTest extends TestCase
{
    public function testGetters(): void
    {
        $model = new Model(
            presets: ['preset_a' => ['foo'], 'preset_b' => ['bar']],
            searchable: ['searchable'],
        );

        $this->assertEquals(['foo'], $model->getPreset('preset_a'));
        $this->assertEquals(['bar'], $model->getPreset('preset_b'));
        $this->assertEquals(['searchable'], $model->getSearchable());
    }
}
