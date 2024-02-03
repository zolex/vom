<?php

/*
 * This file is part of the VOM package.
 *
 * (c) Andreas Linden <zlx@gmx.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\PropertyAccess\PropertyAccess;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Serializer\Normalizer\BooleanNormalizer;
use Zolex\VOM\Serializer\Normalizer\CommonFlagNormalizer;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Symfony\PropertyInfo\PropertyInfoExtractorFactory;
use Zolex\VOM\Symfony\Serializer\SerializerFactory;
use Zolex\VOM\Test\Fixtures\Booleans;
use Zolex\VOM\Test\Fixtures\Calls;
use Zolex\VOM\Test\Fixtures\CommonFlags;
use Zolex\VOM\Test\Fixtures\ConstructorArguments;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\NestedName;
use Zolex\VOM\Test\Fixtures\PropertyPromotion;

class SimpleTest extends PHPUnit\Framework\TestCase
{
    private Symfony\Component\Serializer\Serializer $serializer;

    protected function setUp(): void
    {
        $propertyInfo = PropertyInfoExtractorFactory::create();
        $modelMetadataFactory = new ModelMetadataFactory($propertyInfo);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $objectNormalizer = new ObjectNormalizer($modelMetadataFactory, $propertyAccessor);
        $booleanNormalizer = new BooleanNormalizer();
        $commonFlagNormalizer = new CommonFlagNormalizer();
        $this->serializer = SerializerFactory::create($objectNormalizer, $booleanNormalizer, $commonFlagNormalizer);
    }

    public function testBooleans(): void
    {
        $data = [];

        /* @var Booleans $booleans */
        $booleans = $this->serializer->denormalize($data, Booleans::class);
        $this->assertFalse(isset($booleans->bool));
        $this->assertFalse(isset($booleans->nullableBool));

        $data = [
            'bool' => 'ON',
            'nullableBool' => 'no',
        ];

        /* @var Booleans $booleans */
        $booleans = $this->serializer->denormalize($data, Booleans::class);
        $this->assertTrue($booleans->bool);
        $this->assertFalse($booleans->nullableBool);

        $data = [
            'nullableBool' => null,
        ];

        /* @var Booleans $booleans */
        $booleans = $this->serializer->denormalize($data, Booleans::class);
        $this->assertFalse(isset($booleans->bool));
        $this->assertNull($booleans->nullableBool);
    }

    public function testDateAndTime(): void
    {
        $data = [];

        /* @var DateAndTime $dateAndTime */
        $dateAndTime = $this->serializer->denormalize($data, DateAndTime::class);
        $this->assertFalse(isset($dateAndTime->dateTime));
        $this->assertFalse(isset($dateAndTime->dateTimeImmutable));

        $data = [
            'dateTime' => '2024-02-03 13:05:00',
            'dateTimeImmutable' => '1985-01-20 12:34:56',
        ];

        /* @var DateAndTime $dateAndTime */
        $dateAndTime = $this->serializer->denormalize($data, DateAndTime::class);
        $this->assertTrue(isset($dateAndTime->dateTime));
        $this->assertTrue(isset($dateAndTime->dateTimeImmutable));

        $this->assertEquals($data['dateTime'], $dateAndTime->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals($data['dateTimeImmutable'], $dateAndTime->dateTimeImmutable->format('Y-m-d H:i:s'));
    }

    public function testCommonFlags()
    {
        $data = [
            'flagA',
            '!flagB',
        ];

        /* @var CommonFlags $commonFlags */
        $commonFlags = $this->serializer->denormalize($data, CommonFlags::class);
        $this->assertTrue($commonFlags->flagA);
        $this->assertFalse($commonFlags->flagB);
        $this->assertFalse(isset($commonFlags->flagC));
    }

    public function testAccessor(): void
    {
        $data = [
            'nested' => [
                'firstname' => 'Andreas',
                'deeper' => [
                    'surname' => 'Linden',
                ],
            ],
        ];

        /* @var NestedName $nestedName */
        $nestedName = $this->serializer->denormalize($data, NestedName::class);
        $this->assertEquals($data['nested']['firstname'], $nestedName->firstname);
        $this->assertEquals($data['nested']['deeper']['surname'], $nestedName->lastname);
    }

    public function testArrayOfModels(): void
    {
        $data = [
            [
                'nested' => [
                    'firstname' => 'Andreas',
                    'deeper' => [
                        'surname' => 'Linden',
                    ],
                ],
            ],
            [
                'nested' => [
                    'firstname' => 'Javier',
                    'deeper' => [
                        'surname' => 'Caballero',
                    ],
                ],
            ],
            [
                'nested' => [
                    'firstname' => 'Peter',
                    'deeper' => [
                        'surname' => 'Enis',
                    ],
                ],
            ],
        ];

        /* @var array|NestedName[] $nestedName */
        $nestedNames = $this->serializer->denormalize($data, NestedName::class.'[]');

        $this->assertIsArray($nestedNames);
        $this->assertCount(3, $nestedNames);
        foreach ($data as $index => $item) {
            $this->assertEquals($item['nested']['firstname'], $nestedNames[$index]->firstname);
            $this->assertEquals($item['nested']['deeper']['surname'], $nestedNames[$index]->lastname);
        }
    }

    public function testConstruct(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Pan',
            'nullable' => false,
            'default' => true,
        ];

        $constructed = $this->serializer->denormalize($data, ConstructorArguments::class);
        $this->assertEquals(42, $constructed->getId());
        $this->assertEquals('Peter Pan', $constructed->getName());
        $this->assertFalse($constructed->getNullable());
        $this->assertTrue($constructed->getDefault());
    }

    public function testPropertyPromotion(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Pan',
        ];

        $constructed = $this->serializer->denormalize($data, PropertyPromotion::class);
        $this->assertEquals(42, $constructed->getId());
        $this->assertEquals('Peter Pan', $constructed->getName());
        $this->assertNull($constructed->getNullable());
        $this->assertTrue($constructed->getDefault());

        $data = [
            'id' => 42,
            'name' => 'Peter Pan',
            'default' => false,
            'nullable' => true,
        ];

        $constructed = $this->serializer->denormalize($data, PropertyPromotion::class);
        $this->assertEquals(42, $constructed->getId());
        $this->assertEquals('Peter Pan', $constructed->getName());
        $this->assertTrue($constructed->getNullable());
        $this->assertfalse($constructed->getDefault());
    }

    public function testMethodCalls(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
        ];

        $calls = $this->serializer->denormalize($data, Calls::class);
        $this->assertEquals(42, $calls->getId());
        $this->assertEquals('Peter Enis', $calls->getName());
    }
}
