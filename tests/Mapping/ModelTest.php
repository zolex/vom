<?php

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