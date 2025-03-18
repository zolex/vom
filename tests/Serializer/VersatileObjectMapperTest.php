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

namespace Zolex\VOM\Test\Serializer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\Serializer\Exception\CircularReferenceException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Zolex\VOM\Exception\InvalidArgumentException;
use Zolex\VOM\Metadata\Exception\FactoryException;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\Exception\MissingTypeException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Serializer\Factory\VersatileObjectMapperFactory;
use Zolex\VOM\Serializer\VersatileObjectMapper;
use Zolex\VOM\Test\Fixtures\Address;
use Zolex\VOM\Test\Fixtures\Arrays;
use Zolex\VOM\Test\Fixtures\Booleans;
use Zolex\VOM\Test\Fixtures\Calls;
use Zolex\VOM\Test\Fixtures\CallsOnInvalidDenormalizer;
use Zolex\VOM\Test\Fixtures\CallsOnInvalidNormalizer;
use Zolex\VOM\Test\Fixtures\CallWithArray;
use Zolex\VOM\Test\Fixtures\CallWithObject;
use Zolex\VOM\Test\Fixtures\CallWithObjectInput;
use Zolex\VOM\Test\Fixtures\CallWithUnsupportedArray;
use Zolex\VOM\Test\Fixtures\CallWithUnsupportedClass;
use Zolex\VOM\Test\Fixtures\CircularReference;
use Zolex\VOM\Test\Fixtures\CollectionOfCollections;
use Zolex\VOM\Test\Fixtures\CollectionPublic;
use Zolex\VOM\Test\Fixtures\CollectionWithAdderRemover;
use Zolex\VOM\Test\Fixtures\CollectionWithMutator;
use Zolex\VOM\Test\Fixtures\ConstructorArguments;
use Zolex\VOM\Test\Fixtures\DateAndTime;
use Zolex\VOM\Test\Fixtures\DenormalizerDependency;
use Zolex\VOM\Test\Fixtures\DenormalizerMissingDependency;
use Zolex\VOM\Test\Fixtures\Doctrine\DoctrinePerson;
use Zolex\VOM\Test\Fixtures\FirstAndLastname;
use Zolex\VOM\Test\Fixtures\FirstAndLastnameObject;
use Zolex\VOM\Test\Fixtures\Floats;
use Zolex\VOM\Test\Fixtures\Instantiable;
use Zolex\VOM\Test\Fixtures\InstantiableWithDocTag;
use Zolex\VOM\Test\Fixtures\MixedProperty;
use Zolex\VOM\Test\Fixtures\ModelWithCallableFactory;
use Zolex\VOM\Test\Fixtures\ModelWithFactory;
use Zolex\VOM\Test\Fixtures\ModelWithInvalidFactory;
use Zolex\VOM\Test\Fixtures\ModelWithNonPublicFactory;
use Zolex\VOM\Test\Fixtures\ModelWithNonStaticFactory;
use Zolex\VOM\Test\Fixtures\MultiTypeProps;
use Zolex\VOM\Test\Fixtures\NestingRoot;
use Zolex\VOM\Test\Fixtures\NonInstantiable;
use Zolex\VOM\Test\Fixtures\Person;
use Zolex\VOM\Test\Fixtures\PrivateDenormalizer;
use Zolex\VOM\Test\Fixtures\PrivateNormalizer;
use Zolex\VOM\Test\Fixtures\PropertyPromotion;
use Zolex\VOM\Test\Fixtures\StaticDenormalizer;
use Zolex\VOM\Test\Fixtures\StaticNormalizer;
use Zolex\VOM\Test\Fixtures\Thing;
use Zolex\VOM\Test\Fixtures\ValueMap;

/**
 * Test VOM with a fresh instance for each test.
 */
class VersatileObjectMapperTest extends TestCase
{
    protected static VersatileObjectMapper $serializer;
    protected static ParameterBag $parameterBag;

    public function __construct(string $name)
    {
        parent::__construct($name);

        self::$parameterBag = new ParameterBag();
        self::$parameterBag->set('example', true);
    }

    protected function setUp(): void
    {
        self::$serializer = VersatileObjectMapperFactory::create(null, [self::$parameterBag]);
    }

    public function testNormalizerException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The decorated serializer must implement the NormalizerInterface');
        new VersatileObjectMapper(new DummySerializer());
    }

    public function testDenormalizerException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The decorated serializer must implement the DenormalizerInterface');
        new VersatileObjectMapper(new DummyNormalizer());
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

        /* @var FirstAndLastname $nestedName */
        $nestedName = self::$serializer->denormalize($data, FirstAndLastname::class);
        $this->assertEquals($data['nested']['firstname'], $nestedName->firstname);
        $this->assertEquals($data['nested']['deeper']['surname'], $nestedName->lastname);
    }

    public function testObjectAccessorThrowsExceptionForNormalization(): void
    {
        $data = [
            'nested' => (object) [
                'firstname' => 'Andreas',
                'deeper' => (object) [
                    'surname' => 'Linden',
                ],
            ],
        ];

        /* @var FirstAndLastnameObject $nestedName */
        $names = self::$serializer->denormalize($data, FirstAndLastnameObject::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalization is only supported with array-access syntax. Accessor "[nested].firstname" on class "Zolex\VOM\Test\Fixtures\FirstAndLastnameObject" uses object syntax and therefore can not be normalized.');
        self::$serializer->normalize($names);
    }

    public function testNonObjectAccessorNotFound(): void
    {
        $data = [
            'nested' => (object) [
                'firstname' => 'Andreas',
            ],
        ];

        $this->expectException(NoSuchPropertyException::class);
        self::$serializer->denormalize($data, FirstAndLastnameObject::class);
    }

    public function testJsonNumberToFloat(): void
    {
        $data = ['value' => 3];

        /* @var Floats $floats */
        $floats = self::$serializer->denormalize($data, Floats::class, 'json');
        $normalized = self::$serializer->normalize($floats);
        $this->assertEquals($data, $normalized);
    }

    public function testMixed(): void
    {
        $this->expectException(MissingTypeException::class);
        $this->expectExceptionMessage('Could not determine the type of property "mixed" on class "Zolex\VOM\Test\Fixtures\MixedProperty".');
        self::$serializer->denormalize([], MixedProperty::class);
    }

    /**
     * @dataProvider provideBooleans
     */
    public function testBooleans($data, $expected): void
    {
        /* @var Booleans $booleans */
        $booleans = self::$serializer->denormalize($data, Booleans::class);
        $normalized = self::$serializer->normalize($booleans);
        $this->assertEquals($expected, $normalized);
    }

    public function testStrictBoolWithInvalidValueThrowsException(): void
    {
        $data = [
            'anotherBool' => 'SOMETHING_INVALID',
        ];

        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('string(17) "SOMETHING_INVALID" on attribute "anotherBool" for class "Zolex\VOM\Test\Fixtures\Booleans" could not be normalized to a boolean and the property is not nullable. Check the VOM Property config and/or the data to be normalized');
        self::$serializer->denormalize($data, Booleans::class);
    }

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        self::$serializer->denormalize(['bool' => new \stdClass()], Booleans::class);
    }

    public function testNullableBooleans(): void
    {
        $data = [];

        /* @var Booleans $booleans */
        $booleans = self::$serializer->denormalize($data, Booleans::class);
        $this->assertFalse(isset($booleans->bool));
        $this->assertFalse(isset($booleans->nullableBool));

        $normalized = self::$serializer->normalize($booleans);
        $this->assertArrayNotHasKey('bool', $normalized);
        $this->assertArrayHasKey('nullableBool', $normalized);
        $this->assertNull($normalized['nullableBool']);
    }

    public function testNullableBooleanExplicitlyNull(): void
    {
        $data = [
            'nullableBool' => null,
        ];

        /* @var Booleans $booleans */
        $booleans = self::$serializer->denormalize($data, Booleans::class);
        $this->assertFalse(isset($booleans->bool));
        $this->assertNull($booleans->nullableBool);
    }

    public static function provideBooleans(): iterable
    {
        yield [
            [
                // bool has no explicit true-value configured
                'bool' => 1,
                'nullableBool' => 0,
                'stringBool' => 'yeah',
                'anotherBool' => 'FALSE',
            ],
            [
                // to the result will be true for anything in the default true-values list
                'bool' => true,
                'nullableBool' => false,
                'stringBool' => 'yeah',
                'anotherBool' => 'FALSE',
            ],
        ];

        yield [
            [
                'bool' => true,
                'nullableBool' => 'NO',
                'stringBool' => 'nope',
                'anotherBool' => 'TRUE',
            ],
            [
                'bool' => true,
                'nullableBool' => false,
                'stringBool' => 'nope',
                'anotherBool' => 'TRUE',
            ],
        ];

        yield [
            [
                'nullableBool' => 'INVALID_VALUE_FOR_NULLABLE_BOOL',
            ],
            [
                'nullableBool' => null,
            ],
        ];

        yield [
            [],
            [
                // only bools that are nullable can be null :P
                // rest must be uninitialized
                'nullableBool' => null,
            ],
        ];
    }

    public function testCircularReferenceRethrowsException(): void
    {
        $ref1 = new CircularReference();
        $ref1->id = 1;

        $ref2 = new CircularReference();
        $ref2->id = 2;

        $ref1->reference = $ref2;
        $ref2->reference = $ref1;

        $expected = [
            'id' => 1,
            'reference' => [
                'id' => 2,
            ],
        ];

        $this->expectException(CircularReferenceException::class);
        $this->expectExceptionMessage('Consider adding "circular_reference_handler" or "skip_circular_reference" to the context.');
        $normalized = self::$serializer->normalize($ref1);
        $this->assertEquals($expected, $normalized);
    }

    public function testIngoreCircularReferenceException(): void
    {
        $ref1 = new CircularReference();
        $ref1->id = 1;

        $ref2 = new CircularReference();
        $ref2->id = 2;

        $ref1->reference = $ref2;
        $ref2->reference = $ref1;

        $expected = [
            'id' => 1,
            'reference' => [
                'id' => 2,
            ],
        ];

        $normalized = self::$serializer->normalize($ref1, null, ['skip_circular_reference' => true]);
        $this->assertEquals($expected, $normalized);
    }

    public function testCustomCircularReferenceHandler(): void
    {
        $ref1 = new CircularReference();
        $ref1->id = 1;

        $ref2 = new CircularReference();
        $ref2->id = 2;

        $ref1->reference = $ref2;
        $ref2->reference = $ref1;

        $expected = [
            'id' => 1,
            'reference' => [
                'id' => 2,
                'reference' => [
                    'id' => 1,
                    'reference' => [
                        'id' => 2,
                        'reference' => '/ref/1',
                    ],
                ],
            ],
        ];

        $normalized = self::$serializer->normalize($ref1, null, [
            'circular_reference_limit' => 2,
            'circular_reference_handler' => function ($ref) {
                return \sprintf('/ref/%d', $ref->id);
            },
        ]);
        $this->assertEquals($expected, $normalized);
    }

    public function testDenormalizePersonWithClassDiscriminator(): void
    {
        $data = [
            'type' => 'person',
            'id' => 666,
            'name' => [
                'firstname' => 'Peter',
                'lastname' => 'Enis',
            ],
        ];

        $thing = self::$serializer->denormalize($data, Thing::class);
        $this->assertInstanceOf(Person::class, $thing);

        $normalized = self::$serializer->normalize($thing);
        $this->assertEquals($data, $normalized);
    }

    public function testDenormalizeAddressWithClassDiscriminator(): void
    {
        $data = [
            'type' => 'address',
            'street' => 'Examplestreet',
        ];

        $thing = self::$serializer->denormalize($data, Thing::class);
        $this->assertInstanceOf(Address::class, $thing);

        $normalized = self::$serializer->normalize($thing);
        $this->assertEquals($data, $normalized);
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

        /* @var array|FirstAndLastname[] $nestedName */
        $nestedNames = self::$serializer->denormalize($data, FirstAndLastname::class.'[]');

        $this->assertIsArray($nestedNames);
        $this->assertCount(3, $nestedNames);
        foreach ($data as $index => $item) {
            $this->assertEquals($item['nested']['firstname'], $nestedNames[$index]->firstname);
            $this->assertEquals($item['nested']['deeper']['surname'], $nestedNames[$index]->lastname);
        }
    }

    public function testArrayOfArrays(): void
    {
        $data = [
            'array' => [
                [1, 2, 3],
                [4, 5, 6],
            ],
            'collection' => [
                [
                    ['dateTime' => '2011-05-15 10:11:12'],
                    ['dateTime' => '2012-06-16 10:11:12'],
                ],
                [
                    ['dateTime' => '2013-07-15 10:11:12'],
                    ['dateTime' => '2014-08-15 10:11:12'],
                ],
            ],
        ];

        $collection = self::$serializer->denormalize($data, CollectionOfCollections::class);
        $normalized = self::$serializer->normalize($collection);
        $this->assertEquals($data, $normalized);
    }

    public function testArrayOnRoot(): void
    {
        $data = [
            ['dateTime' => '2024-01-01 00:00:00'],
            ['dateTime' => '2024-01-02 00:00:00'],
            ['dateTime' => '2024-01-03 00:00:00'],
        ];

        /** @var DateAndTime[] $arrayOfDateAndTime */
        $arrayOfDateAndTime = self::$serializer->denormalize($data, DateAndTime::class.'[]');
        $this->assertCount(3, $arrayOfDateAndTime);
        $this->assertEquals('2024-01-01 00:00:00', $arrayOfDateAndTime[0]->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-02 00:00:00', $arrayOfDateAndTime[1]->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-03 00:00:00', $arrayOfDateAndTime[2]->dateTime->format('Y-m-d H:i:s'));
    }

    public function testPublicCollectionThrowsException(): void
    {
        $data = [
            'people' => [
                [
                    'type' => 'person',
                    'id' => 1,
                    'name' => [
                        'firstname' => 'Andreas',
                    ],
                    'address' => [
                        'type' => 'address',
                    ],
                ],
            ],
        ];

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('The property "Zolex\VOM\Test\Fixtures\CollectionPublic::$people" seems to implement ArrayAccess. To allow VOM denormalizing it, create adder/remover methods or a mutator method accepting an array.');
        self::$serializer->denormalize($data, CollectionPublic::class);
    }

    public function testArrayAccessCollectionWithMutator(): void
    {
        $data = [
            'people' => [
                [
                    'type' => 'person',
                    'id' => 1,
                    'name' => [
                        'firstname' => 'Andreas',
                        'lastname' => 'Linden',
                    ],
                    'address' => [
                        'type' => 'address',
                        'street' => 'Elmstreet',
                        'housenumber' => '123',
                    ],
                ], [
                    'type' => 'person',
                    'id' => 2,
                    'name' => [
                        'firstname' => 'Peter',
                        'lastname' => 'Enis',
                    ],
                    'address' => [
                        'type' => 'address',
                        'zipcode' => '54321',
                        'country' => 'DE',
                    ],
                ],
            ],
        ];

        $instantiableNestedCollection = self::$serializer->denormalize($data, CollectionWithMutator::class);
        $this->assertInstanceOf(CollectionWithMutator::class, $instantiableNestedCollection);
        $this->assertInstanceOf(\ArrayObject::class, $instantiableNestedCollection->getPeople());
        $this->assertCount(2, $instantiableNestedCollection->getPeople());

        $normalized = self::$serializer->normalize($instantiableNestedCollection);
        $this->assertEquals($data, $normalized);
    }

    public function testArrayAccessCollectionWithAdderRemover(): void
    {
        $data = [
            'people' => [
                [
                    'type' => 'person',
                    'id' => 1,
                    'name' => [
                        'firstname' => 'Andreas',
                        'lastname' => 'Linden',
                    ],
                    'address' => [
                        'type' => 'address',
                        'street' => 'Elmstreet',
                        'housenumber' => '123',
                    ],
                ], [
                    'type' => 'person',
                    'id' => 2,
                    'name' => [
                        'firstname' => 'Peter',
                        'lastname' => 'Enis',
                    ],
                    'address' => [
                        'type' => 'address',
                        'zipcode' => '54321',
                        'country' => 'DE',
                    ],
                ],
            ],
        ];

        $instantiableNestedCollection = self::$serializer->denormalize($data, CollectionWithAdderRemover::class);
        $this->assertInstanceOf(CollectionWithAdderRemover::class, $instantiableNestedCollection);
        $this->assertInstanceOf(\ArrayObject::class, $instantiableNestedCollection->getPeople());
        $this->assertCount(2, $instantiableNestedCollection->getPeople());

        $normalized = self::$serializer->normalize($instantiableNestedCollection);
        $this->assertEquals($data, $normalized);
    }

    public function testDateAndTime(): void
    {
        $data = [];

        /* @var DateAndTime $dateAndTime */
        $dateAndTime = self::$serializer->denormalize($data, DateAndTime::class);
        $this->assertFalse(isset($dateAndTime->dateTime));
        $this->assertFalse(isset($dateAndTime->dateTimeImmutable));

        $data = [
            'dateTime' => '2024-02-03 13:05:00',
            'dateTimeImmutable' => '1985-01-20 12:34:56',
        ];

        /* @var DateAndTime $dateAndTime */
        $dateAndTime = self::$serializer->denormalize($data, DateAndTime::class);
        $this->assertTrue(isset($dateAndTime->dateTime));
        $this->assertTrue(isset($dateAndTime->dateTimeImmutable));

        $this->assertEquals($data['dateTime'], $dateAndTime->dateTime->format('Y-m-d H:i:s'));
        $this->assertEquals($data['dateTimeImmutable'], $dateAndTime->dateTimeImmutable->format('Y-m-d H:i:s'));
    }

    public function testDecoratedMethods(): void
    {
        $serialized = self::$serializer->serialize([2], 'json', [1]);
        $this->assertEquals('[2]', $serialized);
        $deserialized = self::$serializer->deserialize('[]', DateAndTime::class, 'json');
        $this->assertEquals(new DateAndTime(), $deserialized);

        $supportedTypes = self::$serializer->getSupportedTypes('json');
        $this->assertEquals(['*' => false], $supportedTypes);

        $supportsNormalization = self::$serializer->supportsNormalization(new DateAndTime());
        $this->assertTrue($supportsNormalization);
        $normalized = self::$serializer->normalize(new \DateTime('2010-01-01 00:00:00'), 'json');
        $this->assertEquals('2010-01-01T00:00:00+00:00', $normalized);

        $supportsDenormalization = self::$serializer->supportsDenormalization(['dateTime' => '2010-01-01 10:10:10'], DateAndTime::class);
        $this->assertTrue($supportsDenormalization);

        $denormalized = self::$serializer->denormalize([2], DateAndTime::class);
        $this->assertEquals(new DateAndTime(), $denormalized);
    }

    public function testDoctrineCollection(): void
    {
        $data = [
            'name' => 'Peter Parker',
            'addresses' => [
                [
                    'street' => 'Examplestreet',
                ],
            ],
        ];

        $person = self::$serializer->denormalize($data, DoctrinePerson::class);
        $normalized = self::$serializer->normalize($person);
        $this->assertArrayHasKey('name', $normalized);
        $this->assertEquals($data['name'], $normalized['name']);
        $this->assertArrayHasKey('addresses', $normalized);
        $this->assertEquals($data['addresses'], $normalized['addresses']);
    }

    public function testInstantiableNestedObject(): void
    {
        $factory = new ModelMetadataFactory(PropertyInfoExtractorFactory::create());

        $metadata = $factory->getMetadataFor(Instantiable::class);
        $this->assertInstanceOf(ModelMetadata::class, $metadata);
    }

    public function testNonInstantiableNestedObject(): void
    {
        $this->expectException(NotNormalizableValueException::class);
        $this->expectExceptionMessage('Can not create model metadata for "Zolex\VOM\Test\Fixtures\SomeInterface" because is is a non-instantiable type. Consider to add at least one instantiable type.');
        self::$serializer->denormalize(['property' => []], NonInstantiable::class);
    }

    public function testInstantiableNestedObjectWithPhpDoc(): void
    {
        $instantiable = self::$serializer->denormalize([], InstantiableWithDocTag::class);
        $this->assertInstanceOf(InstantiableWithDocTag::class, $instantiable);
    }

    public function testConstruct(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Pan',
            'nullable' => false,
            'default' => true,
        ];

        $constructed = self::$serializer->denormalize($data, ConstructorArguments::class);
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

        $constructed = self::$serializer->denormalize($data, PropertyPromotion::class);
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

        $constructed = self::$serializer->denormalize($data, PropertyPromotion::class);
        $this->assertEquals(42, $constructed->getId());
        $this->assertEquals('Peter Pan', $constructed->getName());
        $this->assertTrue($constructed->getNullable());
        $this->assertfalse($constructed->getDefault());
    }

    public function testCallableStaticClassMethodFactory(): void
    {
        $data = [
            'name' => 'woohoo',
            'group' => 'something',
        ];

        $model = self::$serializer->denormalize($data, ModelWithCallableFactory::class);
        $this->assertEquals($data['name'], $model->getName());
        $this->assertEquals($data['group'], $model->getGroup());
    }

    public function testInvalidFactoryThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Can not create factory for "Zolex\VOM\Test\Fixtures\ModelWithInvalidFactory". Method Zolex\VOM\Test\Fixtures\RepositoryWithFactory::blah() does not exist');
        self::$serializer->denormalize([], ModelWithInvalidFactory::class);
    }

    public function testNonStaticFactoryThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Factory method "Zolex\VOM\Test\Fixtures\RepositoryWithFactory::nonStaticMethod()" must be static.');
        self::$serializer->denormalize([], ModelWithNonStaticFactory::class);
    }

    public function testNonPublicFactoryThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Factory method "Zolex\VOM\Test\Fixtures\RepositoryWithFactory::nonPublicMethod()" must be public.');
        self::$serializer->denormalize([], ModelWithNonPublicFactory::class);
    }

    public function testFactoryMethod(): void
    {
        $data = [
            'name' => 'woohoo',
            'group' => 'something',
            'flag' => true,
        ];

        $model = self::$serializer->denormalize($data, ModelWithFactory::class);
        $normalized = self::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testFactoryMethodWithUnionType(): void
    {
        $data = [
            'name' => 'woohoo',
            'group' => 123,
            'flag' => false,
        ];

        $model = self::$serializer->denormalize($data, ModelWithFactory::class);
        $normalized = self::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testAlternativeFactoryMethod(): void
    {
        $data = [
            'somethingRequired' => 'yes',
        ];

        $model = self::$serializer->denormalize($data, ModelWithFactory::class);
        $this->assertEquals('yes', $model->getModelName());
    }

    public function testFactoryMethodException(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage('Could not instantiate model "Zolex\VOM\Test\Fixtures\ModelWithFactory" using any of the factory methods (tried "anotherCreate", "create").');
        $this->expectExceptionMessage('- Zolex\VOM\Test\Fixtures\ModelWithFactory::anotherCreate(): Argument #1 ($somethingRequired) must be of type string, null given');
        $this->expectExceptionMessage('- The type of the "name" attribute for class "Zolex\VOM\Test\Fixtures\ModelWithFactory" must be one of "string" ("int" given).');
        self::$serializer->denormalize(['name' => 123], ModelWithFactory::class);
    }

    public function testFactoryReturnsInvalidTypeException(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage('The factory method "Zolex\VOM\Test\Fixtures\ModelWithFactory:invalidReturn()" must return an instance of "Zolex\VOM\Test\Fixtures\ModelWithFactory".');
        self::$serializer->denormalize(['last' => true], ModelWithFactory::class);
    }

    public function testMethodCalls(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
            'data2_id' => 1337,
            'data2_name' => 'Peter Parker',
        ];

        $calls = self::$serializer->denormalize($data, Calls::class, null, ['groups' => ['data', 'more']]);
        $normalized = self::$serializer->normalize($calls, null, ['groups' => ['data', 'more']]);
        $this->assertEquals($data, $normalized);
    }

    public function testMethodWithArrayArgument(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
            'data2_id' => 1337,
            'data2_name' => 'Peter Parker',
        ];

        $calls = self::$serializer->denormalize($data, Calls::class, null, ['groups' => ['data', 'more']]);
        $normalized = self::$serializer->normalize($calls, null, ['groups' => ['data', 'more']]);
        $this->assertEquals($data, $normalized);
    }

    /*
    public function testDenormalizerDependency(): void
    {
        $model = self::$serializer->denormalize([], DenormalizerDependency::class);
        $this->assertTrue($model->example);
    }
    */

    public function testMissingDenormalizerDependencyThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Argument logger of type Psr\Log\LoggerInterface in denormalizer method Zolex\VOM\Test\Fixtures\DenormalizerMissingDependency::denormalizeData() can not be injected. Did you forget to configure it as a denormalizer dependency?');
        self::$serializer->denormalize([], DenormalizerMissingDependency::class);
    }

    public function testGoodDenormalizer(): void
    {
        $calls = new Calls();
        $normalized = self::$serializer->normalize($calls, null, ['groups' => ['good']]);
        $this->assertEquals(['good_string' => 'string'], $normalized);
    }

    public function testBadDenormalizerThrowsException(): void
    {
        $calls = new Calls();
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer Zolex\VOM\Test\Fixtures\Calls::getBadString() without accessor must return an array.');
        self::$serializer->normalize($calls, null, ['groups' => ['bad']]);
    }

    public function testStaticNormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer method Zolex\VOM\Test\Fixtures\StaticNormalizer::staticNormalizer() should not be static.');
        self::$serializer->denormalize([], StaticNormalizer::class);
    }

    public function testPrivateNormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer method Zolex\VOM\Test\Fixtures\PrivateNormalizer::privateNormalizer() must be public.');
        self::$serializer->denormalize([], PrivateNormalizer::class);
    }

    public function testStaticDenormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer method Zolex\VOM\Test\Fixtures\StaticDenormalizer::staticDenormalizer() should not be static.');
        self::$serializer->denormalize([], StaticDenormalizer::class);
    }

    public function testPrivateDenormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer method Zolex\VOM\Test\Fixtures\PrivateDenormalizer::privateDenormalizer() must be public.');
        self::$serializer->denormalize([], PrivateDenormalizer::class);
    }

    public function testMethodCallsOnInvalidDenormalizer(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer on "Zolex\VOM\Test\Fixtures\CallsOnInvalidDenormalizer::bla()" cannot be added.');
        self::$serializer->denormalize([], CallsOnInvalidDenormalizer::class);
    }

    public function testMethodCallsOnInvalidNormalizer(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer on "Zolex\VOM\Test\Fixtures\CallsOnInvalidNormalizer::blubb()" cannot be added.');
        self::$serializer->normalize(new CallsOnInvalidNormalizer());
    }

    public function testMethodCallWithArray(): void
    {
        $result = self::$serializer->denormalize([
            'dates' => [
                '20.01.1985',
                '11.09.2024',
            ],
        ], CallWithArray::class);

        $dates = $result->getDates();
        $this->assertCount(2, $dates);
        $this->assertEquals('1985-01-20', $dates[0]->format('Y-m-d'));
        $this->assertEquals('2024-09-11', $dates[1]->format('Y-m-d'));
    }

    public function testMethodCallWithStdClass(): void
    {
        $result = self::$serializer->denormalize([
            'thing' => (object) [
                'name' => 'fly thing',
            ],
        ], CallWithObject::class);

        $this->assertInstanceOf(CallWithObject::class, $result);
        $this->assertEquals($result->getName(), 'fly thing');
    }

    public function testMethodCallWithCompatibleClass(): void
    {
        $result = self::$serializer->denormalize([
            'thing' => new CallWithObjectInput(name: 'hit thing'),
        ], CallWithObject::class);

        $this->assertInstanceOf(CallWithObject::class, $result);
        $this->assertEquals($result->getName(), 'hit thing');
    }

    public function testMethodCallWithIncompatibleClass(): void
    {
        $result = self::$serializer->denormalize([
            'thing' => new Address(),
        ], CallWithObject::class);

        $this->assertInstanceOf(CallWithObject::class, $result);
        $this->assertNull($result->getName());
    }

    public function testMethodCallWithUnsupportedArrayThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Only scalars are allowed for method call Zolex\VOM\Test\Fixtures\CallWithUnsupportedArray::setArray(). Consider using collection attributes.');
        self::$serializer->denormalize([], CallWithUnsupportedArray::class);
    }

    public function testMethodCallWithUnsupportedClassThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Only builtin types are supported for method call Zolex\VOM\Test\Fixtures\CallWithUnsupportedClass::setObject().');
        self::$serializer->denormalize([], CallWithUnsupportedClass::class);
    }

    public function testDenormalizerWithGroups(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
            'data2_id' => 1337,
            'data2_name' => 'Peter Parker',
        ];

        $calls = self::$serializer->denormalize($data, Calls::class, null, ['groups' => ['data']]);
        $this->assertEquals(['id' => 42, 'name' => 'Peter Enis'], $calls->normalizeData());
        $this->assertEquals([], $calls->getMoreData());

        $calls = self::$serializer->denormalize($data, Calls::class, null, ['groups' => ['more']]);
        $normalizedData = self::$serializer->normalize($calls, null, ['groups' => ['data']]);
        $this->assertEquals([], $normalizedData);
        $normalizedMore = self::$serializer->normalize($calls, null, ['groups' => ['more']]);
        $this->assertEquals(['data2_id' => 1337, 'data2_name' => 'Peter Parker'], $normalizedMore);

        $calls = self::$serializer->denormalize($data, Calls::class);
        $this->assertEquals(['id' => 42, 'name' => 'Peter Enis'], $calls->normalizeData());
        $this->assertEquals(['data2_id' => 1337, 'data2_name' => 'Peter Parker'], $calls->getMoreData());
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
                                        ['dateTime' => '2024-01-06 00:00:00'],
                                        ['dateTime' => '2024-01-07 00:00:00'],
                                        ['dateTime' => '2024-01-08 00:00:00'],
                                        ['dateTime' => '2024-01-09 00:00:00'],
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
                                                ['dateTime' => '2024-01-16 00:00:00'],
                                                ['dateTime' => '2024-01-17 00:00:00'],
                                                ['dateTime' => '2024-01-18 00:00:00'],
                                                ['dateTime' => '2024-01-19 00:00:00'],
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

        $items = self::$serializer->denormalize($data, Arrays::class);
        $data2 = self::$serializer->normalize($items);

        $this->assertEquals($data, $data2);
    }

    /**
     * @dataProvider createNestedModelsDataProvider
     */
    public function testCreateNestedModels(array $data, string $className, string|array $groups, object $expectedModel)
    {
        $model = self::$serializer->denormalize($data, $className, null, ['groups' => $groups]);
        $this->assertEquals($expectedModel, $model);
    }

    public static function createNestedModelsDataProvider(): iterable
    {
        yield [
            [
                'id' => 42,
                'name' => [
                    'firstname' => 'The',
                    'lastname' => 'Dude',
                ],
            ],
            Person::class,
            ['id'],
            Person::create(id: 42),
        ];

        yield [
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
            Person::create(id: 42, firstname: 'The', lastname: 'Dude', age: 38, email: 'some@mail.to'),
        ];

        yield [
            [
                'id' => 42,
                'int_age' => 42,
                'contact_email' => 'some@mail.to',
                'address' => [
                    'street' => 'Fireroad',
                    'housenumber' => '666',
                    'zipcode' => '56070',
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
            ['extended'],
            Person::create(
                id: 42,
                age: 42,
                email: 'some@mail.to',
                isAwesome: true,
                isHilarious: false,
                address: Address::create(
                    street: 'Fireroad',
                    houseNo: '666',
                    zip: '56070',
                    city: 'Hell',
                ),
            ),
        ];

        yield [
            [
                'id' => 43,
                'int_age' => 42,
                'contact_email' => 'some@mail.to',
                'address' => [
                    'street' => 'Fireroad',
                    'housenumber' => '32',
                    'zipcode' => '50210',
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
            Person::create(
                id: 43,
                isHilarious: true,
            ),
        ];

        yield [
            [
                'id' => 44,
                'hilarious' => 'ON',
                'address' => [
                    'street' => 'Fireroad',
                    'housenumber' => '213456',
                    'zipcode' => '98765',
                    'city' => 'Dunkel',
                ],
            ],
            Person::class,
            ['id', 'address'],
            Person::create(
                id: 44,
                address: Address::create(
                    street: 'Fireroad',
                    houseNo: '213456',
                    zip: '98765',
                    city: 'Dunkel',
                ),
            ),
        ];

        yield [
            [
                'id' => 45,
                'address' => [
                    'street' => 'Elmstreet',
                    'housenumber' => '666',
                ],
            ],
            Person::class,
            ['address'],
            Person::create(
                address: Address::create(
                    street: 'Elmstreet',
                    houseNo: '666',
                ),
            ),
        ];
    }

    public function testRootWithNestedAndAccessors(): void
    {
        $data = [
            'ROOT' => [
                'VALUE' => '0',
            ],
            'LEVEL_ONE' => [
                'LEVEL_ONE_VALUE' => '1',
            ],
            'LEVEL_TWO_VALUE' => '2',
            'LEVEL_THREE' => [
                'LEVEL_FOUR' => [
                    'LEVEL_FOUR_VALUE' => '4',
                ],
            ],
            'NESTED' => [
                'LEVEL_THREE_VALUE' => '3',
            ],
        ];

        $nestingRoot = self::$serializer->denormalize($data, NestingRoot::class);
        $normalized = self::$serializer->normalize($nestingRoot);

        $this->assertEquals($data, $normalized);
    }

    public function testObjectToPopulate(): void
    {
        $person = Person::create(id: 666);

        $data = [
            'name' => [
                'firstname' => 'Peter',
                'lastname' => 'Parker',
            ],
        ];

        $person2 = self::$serializer->denormalize($data, Person::class, null, ['object_to_populate' => $person]);
        $this->assertSame($person, $person2);
        $this->assertEquals('Peter', $person2->firstname);
        $this->assertEquals('Parker', $person2->lastname);
    }

    public function testToObject(): void
    {
        $data = [
            (object) [
                'prop' => 'val',
            ],
            [
                'prop' => 'val',
                'another' => [
                    'prop' => 'val',
                ],
            ],
        ];

        $expected = [
            (object) [
                'prop' => 'val',
            ],
            (object) [
                'prop' => 'val',
                'another' => (object) [
                    'prop' => 'val',
                ],
            ],
        ];

        $obj = VersatileObjectMapper::toObject($data);
        $this->assertEquals($expected, $obj);
    }

    public function testDenormalizeUnionType(): void
    {
        $model = self::$serializer->denormalize(['value' => 42], MultiTypeProps::class, null, ['disable_type_enforcement' => true]);
        $this->assertEquals(42, $model->value);

        $model = self::$serializer->denormalize(['value' => 13.37], MultiTypeProps::class, null, ['disable_type_enforcement' => true]);
        $this->assertEquals(13.37, $model->value);
    }

    public function testValueMapping(): void
    {
        $model = self::$serializer->denormalize([
            'type' => 'TYPE1',
            'color' => 'RED',
        ], ValueMap::class);

        $this->assertEquals('A', $model->type);
        $this->assertEquals('#FF0000', $model->color);
    }

    public function testValueMappingWithInvalidValueAndNoDefaultValue(): void
    {
        $model = self::$serializer->denormalize([
            'type' => 'WRONG_TYPE',
        ], ValueMap::class);

        $this->assertFalse(isset($model->type));
    }

    public function testValueMappingWithInvalidValueAndExistingDefaultValue(): void
    {
        $model = self::$serializer->denormalize([
            'color' => 'RAINBOW',
        ], ValueMap::class);

        $this->assertEquals('#000000', $model->color);
    }

    public function testValueMappingWithNullValue(): void
    {
        $model = self::$serializer->denormalize([
            'nullable' => 'NULL',
        ], ValueMap::class);

        $this->assertNull($model->nullable);
    }
}
