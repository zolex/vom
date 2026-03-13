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
use Zolex\VOM\Test\Fixtures\ConditionalMapping;
use Zolex\VOM\Test\Functional\TestCase\VersatileObjectMapperTestCase;

class ConditionalMappingTest extends TestCase
{
    use VersatileObjectMapperTestCase;

    public function testConditionalMappingCaseA(): void
    {
        $data = [
            'SOURCE_CASE' => 'CASE_A',
            'SOURCE_PARAM_A' => 'First Case Value',
            'SOURCE_PARAM_B' => 'Second Case Value',
        ];

        /** @var ConditionalMapping $model */
        $model = static::$serializer->denormalize($data, ConditionalMapping::class);
        $this->assertSame('First Case Value', $model->paramOne);

        $result = static::$serializer->normalize($model);
        $this->assertSame('First Case Value', $result['SOURCE_PARAM_A']);
    }

    public function testConditionalMappingCaseB(): void
    {
        $data = [
            'SOURCE_CASE' => 'CASE_B',
            'SOURCE_PARAM_A' => 'First Case Value',
            'SOURCE_PARAM_B' => 'Second Case Value',
        ];

        /** @var ConditionalMapping $model */
        $model = static::$serializer->denormalize($data, ConditionalMapping::class);
        $this->assertSame('Second Case Value', $model->paramTwo);
    }

    public function testConditionalMappingCaseC(): void
    {
        $data = [
            'SOURCE_CASE' => 'CASE_C', // non-existent
            'SOURCE_PARAM_A' => 'First Case Value',
            'SOURCE_PARAM_B' => 'Second Case Value',
        ];

        /** @var ConditionalMapping $model */
        $model = static::$serializer->denormalize($data, ConditionalMapping::class);
        $this->assertFalse(isset($model->paramOne));
    }

    /**
     * this actually tests the denormalize expression not the "if" option, bust it's a form of conditional mapping too.
     *
     * @see ConditionalMapping::$paramTwo
     */
    public function testDenormalizeConditional(): void
    {
        $data = [
            'SOURCE_CASE' => 'NON_EXISTING_CASE',
            'SOURCE_PARAM_A' => 'First Case Value',
            'SOURCE_PARAM_B' => 'Second Case Value',
        ];

        /** @var ConditionalMapping $model */
        $model = static::$serializer->denormalize($data, ConditionalMapping::class);
        $this->assertSame('Second Case Value', $model->paramTwo);
    }

    public function testConditionalMappingWithCallableCaseA(): void
    {
        $data = [
            'SOURCE_CASE' => 'CASE_A',
            'SOURCE_PARAM_A' => 'First Case Value',
            'SOURCE_PARAM_B' => 'Second Case Value',
        ];

        /** @var ConditionalMapping $model */
        $model = static::$serializer->denormalize($data, ConditionalMapping::class);
        $this->assertSame('First Case Value', $model->paramThree);
    }

    public function testConditionalMappingWithCallableCaseB(): void
    {
        $data = [
            'SOURCE_CASE' => 'CASE_B',
            'SOURCE_PARAM_A' => 'First Case Value',
            'SOURCE_PARAM_B' => 'Second Case Value',
        ];

        /** @var ConditionalMapping $model */
        $model = static::$serializer->denormalize($data, ConditionalMapping::class);
        $this->assertSame('Second Case Value', $model->paramThree);
    }
}
