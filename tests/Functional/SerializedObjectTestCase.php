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
use Zolex\VOM\Test\Fixtures\SerializedObject;
use Zolex\VOM\Test\Fixtures\SerializedObjectArray;
use Zolex\VOM\Test\Fixtures\SerializedObjectWithAdditionalNormalizer;
use Zolex\VOM\Test\Fixtures\SerializedObjectWithFactory;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class SerializedObjectTestCase extends TestCase
{
    public function testArrayOfSerializedObjects(): void
    {
        $data = [
            'images' => [
                'image1.jpg,tag:foobar,description:source data is shit',
                'barbaz.png,tag:barbaz,description:mind your own business',
            ],
        ];

        $model = static::$serializer->denormalize($data, SerializedObjectArray::class);

        $this->assertInstanceOf(SerializedObjectArray::class, $model);

        $this->assertInstanceOf(SerializedObject::class, $model->images[0]);
        $this->assertEquals('image1.jpg', $model->images[0]->filename);
        $this->assertEquals('foobar', $model->images[0]->tag);
        $this->assertEquals('source data is shit', $model->images[0]->description);

        $this->assertInstanceOf(SerializedObject::class, $model->images[1]);
        $this->assertEquals('barbaz.png', $model->images[1]->filename);
        $this->assertEquals('barbaz', $model->images[1]->tag);
        $this->assertEquals('mind your own business', $model->images[1]->description);

        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testSerializedObjectWithFactory(): void
    {
        $data = 'image1.jpg,tag:foobar,description:source data is shit';
        $model = static::$serializer->denormalize($data, SerializedObjectWithFactory::class);

        $this->assertInstanceOf(SerializedObjectWithFactory::class, $model);
        $this->assertEquals('image1.jpg', $model->filename);
        $this->assertEquals('foobar', $model->tag);
        $this->assertEquals('source data is shit', $model->description);

        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testSerializedObjectWithAdditionalNormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The "__toString()" method on model "Zolex\VOM\Test\Fixtures\SerializedObjectWithAdditionalNormalizer" is configured as a normalizer. There must be no additional normalizer methods.');
        static::$serializer->denormalize([], SerializedObjectWithAdditionalNormalizer::class);
    }
}
