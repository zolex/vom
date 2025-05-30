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

namespace Zolex\VOM\Test\Functional;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Test\Fixtures\RegexpExtractorModel;
use Zolex\VOM\Test\Fixtures\RegexpExtractorProperty;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class RegularExpressionExtractorTestCase extends TestCase
{
    public function testRegexpExtractorModel(): void
    {
        $data = 'image1.jpg,tag:foobar,visibility:hidden';
        $model = static::$serializer->denormalize($data, RegexpExtractorModel::class);

        $this->assertInstanceOf(RegexpExtractorModel::class, $model);
        $this->assertEquals('image1.jpg', $model->filename);
        $this->assertEquals('foobar', $model->tag);
        $this->assertFalse($model->isVisible);

        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testRegexpExtractorProperty(): void
    {
        $data = 'image2.jpg,tag:foobar,visibility:visible';
        $model = static::$serializer->denormalize($data, RegexpExtractorProperty::class);

        $this->assertInstanceOf(RegexpExtractorProperty::class, $model);
        $this->assertEquals('image2.jpg', $model->filename);
        $this->assertEquals('foobar', $model->tag);
        $this->assertTrue($model->isVisible);

        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testRegexpExtractorPropertyThrowsExceptionWhenNotMatching(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Extractor "/tag:([^,]+)/" on "Zolex\VOM\Test\Fixtures\RegexpExtractorProperty::$tag" does not match the data "WRONGDATA"');

        static::$serializer->denormalize('WRONGDATA', RegexpExtractorProperty::class);
    }

    public function testRegexpExtractorModelThrowsExceptionWhenNotMatching(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Extractor "/^(?<filename>.+),tag:(.*),visibility:(?<visibility>visible|hidden)/" on model "Zolex\VOM\Test\Fixtures\RegexpExtractorModel" does not match the data "WRONGDATA"');
        static::$serializer->denormalize('WRONGDATA', RegexpExtractorModel::class);
    }
}
