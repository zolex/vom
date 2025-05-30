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
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Zolex\VOM\Metadata\Exception\FactoryException;
use Zolex\VOM\Metadata\Exception\MappingException;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\ModelMetadata;
use Zolex\VOM\PropertyInfo\Extractor\PropertyInfoExtractorFactory;
use Zolex\VOM\Test\Fixtures\ConstructorArguments;
use Zolex\VOM\Test\Fixtures\Instantiable;
use Zolex\VOM\Test\Fixtures\InstantiableWithDocTag;
use Zolex\VOM\Test\Fixtures\ModelWithCallableFactory;
use Zolex\VOM\Test\Fixtures\ModelWithFactory;
use Zolex\VOM\Test\Fixtures\ModelWithInvalidFactory;
use Zolex\VOM\Test\Fixtures\ModelWithNonPublicFactory;
use Zolex\VOM\Test\Fixtures\ModelWithNonStaticFactory;
use Zolex\VOM\Test\Fixtures\NonInstantiable;
use Zolex\VOM\Test\Fixtures\PropertyPromotion;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class InstanciationTestCase extends TestCase
{
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
        static::$serializer->denormalize(['property' => []], NonInstantiable::class);
    }

    public function testInstantiableNestedObjectWithPhpDoc(): void
    {
        $instantiable = static::$serializer->denormalize([], InstantiableWithDocTag::class);
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

        $constructed = static::$serializer->denormalize($data, ConstructorArguments::class);
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

        $constructed = static::$serializer->denormalize($data, PropertyPromotion::class);
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

        $constructed = static::$serializer->denormalize($data, PropertyPromotion::class);
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

        $model = static::$serializer->denormalize($data, ModelWithCallableFactory::class);
        $this->assertEquals($data['name'], $model->getName());
        $this->assertEquals($data['group'], $model->getGroup());
    }

    public function testInvalidFactoryThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Can not create factory for "Zolex\VOM\Test\Fixtures\ModelWithInvalidFactory". Method Zolex\VOM\Test\Fixtures\RepositoryWithFactory::blah() does not exist');
        static::$serializer->denormalize([], ModelWithInvalidFactory::class);
    }

    public function testNonStaticFactoryThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Factory method "Zolex\VOM\Test\Fixtures\RepositoryWithFactory::nonStaticMethod()" must be static.');
        static::$serializer->denormalize([], ModelWithNonStaticFactory::class);
    }

    public function testNonPublicFactoryThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Factory method "Zolex\VOM\Test\Fixtures\RepositoryWithFactory::nonPublicMethod()" must be public.');
        static::$serializer->denormalize([], ModelWithNonPublicFactory::class);
    }

    public function testFactoryMethod(): void
    {
        $data = [
            'name' => 'woohoo',
            'group' => 'something',
            'flag' => true,
        ];

        $model = static::$serializer->denormalize($data, ModelWithFactory::class);
        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testFactoryMethodWithUnionType(): void
    {
        $data = [
            'name' => 'woohoo',
            'group' => 123,
            'flag' => false,
        ];

        $model = static::$serializer->denormalize($data, ModelWithFactory::class);
        $normalized = static::$serializer->normalize($model);
        $this->assertEquals($data, $normalized);
    }

    public function testAlternativeFactoryMethod(): void
    {
        $data = [
            'somethingRequired' => 'yes',
        ];

        $model = static::$serializer->denormalize($data, ModelWithFactory::class);
        $this->assertEquals('yes', $model->getModelName());
    }

    public function testFactoryMethodException(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage('Could not instantiate model "Zolex\VOM\Test\Fixtures\ModelWithFactory" using any of the factory methods (tried "anotherCreate", "create").');
        $this->expectExceptionMessage('- Zolex\VOM\Test\Fixtures\ModelWithFactory::anotherCreate(): Argument #1 ($somethingRequired) must be of type string, null given');
        $this->expectExceptionMessage('- The type of the "name" attribute for class "Zolex\VOM\Test\Fixtures\ModelWithFactory" must be one of "string" ("int" given).');
        static::$serializer->denormalize(['name' => 123], ModelWithFactory::class);
    }

    public function testFactoryReturnsInvalidTypeException(): void
    {
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage('The factory method "Zolex\VOM\Test\Fixtures\ModelWithFactory:invalidReturn()" must return an instance of "Zolex\VOM\Test\Fixtures\ModelWithFactory".');
        static::$serializer->denormalize(['last' => true], ModelWithFactory::class);
    }
}
