<?php

namespace Zolex\VOM\Test\VersatileObjectMapper;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Zolex\VOM\Metadata\Factory\Exception\RuntimeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\PropertyMetadataFactory;
use Zolex\VOM\Symfony\PropertyInfo\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\Address;
use Zolex\VOM\Test\Fixtures\Arrays;
use Zolex\VOM\Test\Fixtures\ArrayWithoutDocTag;
use Zolex\VOM\Test\Fixtures\Booleans;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\FlagParent;
use Zolex\VOM\Test\Fixtures\Person;
use Zolex\VOM\Test\Fixtures\SickChild;
use Zolex\VOM\Test\Fixtures\SickRoot;
use Zolex\VOM\Test\Fixtures\SickSack;
use Zolex\VOM\Test\Fixtures\SickSuck;
use Zolex\VOM\VersatileObjectMapper;

class VersatileObjectMapperTest extends TestCase
{
    protected VersatileObjectMapper $objectMapper;

    public function setUp(): void
    {
        $propertyInfo = PropertyInfoExtractorFactory::create();

        $modelMetadataFactory = new ModelMetadataFactory($propertyInfo);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->objectMapper = new VersatileObjectMapper($modelMetadataFactory, $propertyAccessor);
    }

    public function testBooleans()
    {
        $model = $this->objectMapper->denormalize(['bool' => 'on', 'nullableBool' => 1], Booleans::class);
        $this->assertTrue($model->bool);
        $this->assertTrue($model->nullableBool);

        $model = $this->objectMapper->denormalize(['bool' => 'off', 'nullableBool' => 0], Booleans::class);
        $this->assertFalse($model->bool);
        $this->assertFalse($model->nullableBool);

        $model = $this->objectMapper->denormalize([], Booleans::class);
        $this->assertFalse(isset($model->bool));
        $this->assertFalse(isset($model->nullableBool));
    }

    /*
    public function testArrayWithoutDocTag(): void
    {
        $this->markTestSkipped('New implementation. Check if we want to throw');

        $this->expectException(RuntimeException::class);
        $data = ['list' => [[], [], []]];
        $this->objectMapper->denormalize($data, ArrayWithoutDocTag::class);
    }
    */

    public function testFlags()
    {
        $data = [
            "commonFlags" => [
                "flagA",
                "!flagB",
            ],
            "somethingElse" => [
                "flagD",
            ],
            "labeledFlagsArray" => [
                "flagA" => (object) [
                    "text" => "Fahne A",
                    "value" => "flagA",
                ],
                "flagB" => [
                    "text" => "Fahne B",
                    "value" => true,
                ],
            ],
            "labeledFlagsObject" => (object) [
                "flagA" => [
                    "text" => "Fahne A",
                    "value" => false,
                ],
                "flagB" => (object) [
                    "text" => "Fahne B",
                    "value" => 'OFF',
                ],
            ],
        ];

        $model = $this->objectMapper->denormalize($data, FlagParent::class);

        $this->assertTrue($model->commonFlags->flagA);
        $this->assertFalse($model->commonFlags->flagB);
        $this->assertNull($model->commonFlags->flagC);

        $this->assertTrue($model->labeledFlagsArray->flagA->isEnabled);
        $this->assertTrue($model->labeledFlagsArray->flagB->isEnabled);
        $this->assertNull($model->labeledFlagsArray->flagC);

        $this->assertFalse($model->labeledFlagsObject->flagA->isEnabled);
        $this->assertFalse($model->labeledFlagsObject->flagB->isEnabled);
        $this->assertNull($model->labeledFlagsObject->flagC);
    }

    public function testDateTime()
    {
        $model = $this->objectMapper->denormalize([], DateAndTime::class);
        $this->assertEquals(new DateAndTime(), $model);

        $model = $this->objectMapper->denormalize(['dateTime' => null, 'dateTimeImmutable' => null], DateAndTime::class);
        $this->assertEquals(new DateAndTime(), $model);

        $model = $this->objectMapper->denormalize([
            'dateTime' => '2024-01-20 06:00:00',
            'dateTimeImmutable' => '2001-04-11 14:14:00',
        ], DateAndTime::class);

        $expected = new DateAndTime();
        $expected->dateTime = new \DateTime('2024-01-20 06:00:00');
        $expected->dateTimeImmutable = new \DateTimeImmutable('2001-04-11 14:14:00');
        $this->assertEquals($expected, $model);
    }

    public function testConversions()
    {
        $root = new SickRoot();
        $root = new SickRoot();

        $child1 = new SickChild();
        $child1->firstname = 'Javier';
        $child1->hasHair = true;
        $root->singleChild = $child1;

        $child2 = new SickChild();
        $child2->firstname = 'Andreas';
        $child2->hasHair = false;
        $root->anotherChild = $child2;

        $child3 = new SickChild();
        $child3->firstname = 'Peter';
        $child3->hasHair = true;
        $root->tooManyChildren[] = $child3;

        $child4 = new SickChild();
        $child4->firstname = 'Hank';
        $child4->hasHair = false;
        $root->tooManyChildren[] = $child4;

        $sickSuck = new SickSuck();
        $sickSuck->sickedy = 'sickedysick';
        $sickSuck->sackedy = 'sackedysack';

        $sickSack = new SickSack();
        $sickSack->sick = 1337;
        $sickSack->sack = 'sackywacky';
        $sickSack->sickSuck = $sickSuck;
        $root->sickSack = $sickSack;

        $array1 = $this->objectMapper->normalize($root);
        $model1 = $this->objectMapper->denormalize($array1, SickRoot::class);

        $object1 = $this->objectMapper->toObject($this->objectMapper->normalize($root));
        $object2 = $this->objectMapper->toObject($this->objectMapper->normalize($model1));

        $model2 = $this->objectMapper->denormalize($object2, SickRoot::class);

        $this->assertEquals($root, $model1);
        $this->assertEquals($root, $model2);
        $this->assertEquals($object1, $object2);
    }

    /**
     * @dataProvider createModelDataProvider
     */
    public function testCreateModel(array $data, string $className, string|array $groups, object $expectedModel)
    {
        $model = $this->objectMapper->denormalize($data, $className, null, ['groups' => $groups]);
        $this->assertEquals($expectedModel, $model);
    }

    public function createModelDataProvider(): array
    {
        return [
            /*
            [
                [
                    'id' => 42,
                    'name' => [
                        'firstname' => 'The',
                        'lastname' => 'Dude',
                    ],
                ],
                Person::class,
                ['id'],
                new Person(id: 42),
            ],
            [
                [
                    'id' => 42,
                    'name' => [
                        'firstname' => 'The',
                        'lastname' => 'Dude',
                    ],
                    'int_age' => 38,
                    'contact_email' => 'some@mail.to',
                    'address' => [
                        'street' => 'nowhere',
                    ],
                ],
                Person::class,
                ['standard'],
                new Person(id: 42, firstname: 'The', lastname: 'Dude', age: 38, email: 'some@mail.to'),
            ],
            */
            [
                [
                    'id' => 42,
                    'int_age' => 42,
                    'contact_email' => 'some@mail.to',
                    'address' => [
                        'street' => 'Fireroad',
                        'housenumber' => 666,
                        'zipcode' => 56070,
                        'city' => 'Hell',
                    ],
                    'bool_awesome' => 'y',
                    'hilarious' => 'OFF',
                    'flags' => [
                        'delicious' => 'delicious',
                        'holy' => 'holy',
                    ],
                ],
                Person::class,
                'extended',
                new Person(
                    id: 42,
                    age: 42,
                    email: 'some@mail.to',
                    isAwesome: true,
                    isHilarious: false,
                    address: new Address(
                        street: 'Fireroad',
                        houseNo: 666,
                        zip: 56070,
                        city: 'Hell',
                    ),
                ),
            ],
            [
                [
                    'id' => 43,
                    'int_age' => 42,
                    'contact_email' => 'some@mail.to',
                    'address' => [
                        'street' => 'Fireroad',
                        'housenumber' => 666,
                        'zipcode' => 56070,
                        'city' => 'Hell',
                    ],
                    'bool_awesome' => 'true',
                    'hilarious' => 'ON',
                    'flags' => [
                        'delicious' => 'delicious',
                    ],
                ],
                Person::class,
                ['id', 'isHoly', 'isHilarious'],
                new Person(
                    id: 43,
                    isHilarious: true,
                ),
            ],
            [
                [
                    'id' => 44,
                    'hilarious' => 'ON',
                    'address' => [
                        'street' => 'Fireroad',
                        'housenumber' => 666,
                        'zipcode' => 56070,
                        'city' => 'Hell',
                    ],
                ],
                Person::class,
                ['id', 'address'],
                new Person(
                    id: 44,
                    address: new Address(
                        street: 'Fireroad',
                        houseNo: 666,
                        zip: 56070,
                        city: 'Hell',
                    ),
                ),
            ],
            [
                [
                    'id' => 45,
                    'address' => [
                        'street' => 'Fireroad',
                        'housenumber' => 666,
                    ],
                ],
                Person::class,
                ['address'],
                new Person(
                    address: new Address(
                        street: 'Fireroad',
                        houseNo: 666,
                        zip: null,
                        city: null,
                        country: null
                    ),
                ),
            ],
        ];
    }

    public function testArrayOnRoot(): void
    {
        $data = [
            ['dateTime' => '2024-01-01 00:00:00'],
            ['dateTime' => '2024-01-02 00:00:00'],
            ['dateTime' => '2024-01-03 00:00:00'],
        ];

        /** @var DateAndTime[] $arrayOfDateAndTime */
        $arrayOfDateAndTime = $this->objectMapper->denormalize($data, DateAndTime::class.'[]');
        $this->assertCount(3, $arrayOfDateAndTime);
        $this->assertEquals('2024-01-01 00:00:00', $arrayOfDateAndTime[0]->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-02 00:00:00', $arrayOfDateAndTime[1]->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-03 00:00:00', $arrayOfDateAndTime[2]->dateTime->format('Y-m-d H:i:s'));
    }

    public function testRecursiveStructures(): void
    {
        $data = [
            'dateTimeList' => [
                ['dateTime' => '2024-01-01 00:00:00'],
            ],
            'recursiveList' => [
                [
                    'dateTimeList' => [
                        ['dateTime' => '2024-01-02 00:00:00'],
                        ['dateTime' => '2024-01-03 00:00:00'],
                    ],
                    'recursiveList' => [
                        [
                            'dateTimeList' => [
                                ['dateTime' => '2024-01-03 00:00:00'],
                                ['dateTime' => '2024-01-04 00:00:00'],
                                ['dateTime' => '2024-01-05 00:00:00'],
                            ],
                            'recursiveList' => [
                                [
                                    'dateTimeList' => [
                                        ['dateTime' => '2024-06-01 00:00:00'],
                                        ['dateTime' => '2024-07-01 00:00:00'],
                                        ['dateTime' => '2024-08-01 00:00:00'],
                                        ['dateTime' => '2024-09-01 00:00:00'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'dateTimeList' => [
                        ['dateTime' => '2024-01-10 00:00:00'],
                    ],
                    'recursiveList' => [
                        [
                            'dateTimeList' => [
                                ['dateTime' => '2024-01-11 00:00:00'],
                                ['dateTime' => '2024-01-12 00:00:00'],
                            ],
                            'recursiveList' => [
                                [
                                    'dateTimeList' => [
                                        ['dateTime' => '2024-01-13 00:00:00'],
                                        ['dateTime' => '2024-01-14 00:00:00'],
                                        ['dateTime' => '2024-01-15 00:00:00'],
                                    ],
                                    'recursiveList' => [
                                        [
                                            'dateTimeList' => [
                                                ['dateTime' => '2024-06-16 00:00:00'],
                                                ['dateTime' => '2024-07-17 00:00:00'],
                                                ['dateTime' => '2024-08-18 00:00:00'],
                                                ['dateTime' => '2024-09-19 00:00:00'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $items = $this->objectMapper->denormalize($data, Arrays::class);
        $data2 = $this->objectMapper->normalize($items);

        $this->assertEquals($data, $data2);
    }
}
