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
use Zolex\VOM\Test\Fixtures\Address;
use Zolex\VOM\Test\Fixtures\Calls;
use Zolex\VOM\Test\Fixtures\CallsOnInvalidDenormalizer;
use Zolex\VOM\Test\Fixtures\CallsOnInvalidNormalizer;
use Zolex\VOM\Test\Fixtures\CallWithArray;
use Zolex\VOM\Test\Fixtures\CallWithObject;
use Zolex\VOM\Test\Fixtures\CallWithObjectInput;
use Zolex\VOM\Test\Fixtures\CallWithUnsupportedArray;
use Zolex\VOM\Test\Fixtures\CallWithUnsupportedClass;
use Zolex\VOM\Test\Fixtures\DependencyInConstructor;
use Zolex\VOM\Test\Fixtures\DependencyInConstructorMissing;
use Zolex\VOM\Test\Fixtures\DependencyInConstructorPropertyPromotion;
use Zolex\VOM\Test\Fixtures\DependencyInDenormalizer;
use Zolex\VOM\Test\Fixtures\DependencyInDenormalizerMissing;
use Zolex\VOM\Test\Fixtures\DependencyInFactory;
use Zolex\VOM\Test\Fixtures\DependencyInFactoryMissing;
use Zolex\VOM\Test\Fixtures\DependencyInNormalizer;
use Zolex\VOM\Test\Fixtures\DependencyInNormalizerMissing;
use Zolex\VOM\Test\Fixtures\PrivateDenormalizer;
use Zolex\VOM\Test\Fixtures\PrivateNormalizer;
use Zolex\VOM\Test\Fixtures\StaticDenormalizer;
use Zolex\VOM\Test\Fixtures\StaticNormalizer;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class MethodCallsTestCase extends TestCase
{
    public function testMethodCalls(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
            'data2_id' => 1337,
            'data2_name' => 'Peter Parker',
        ];

        $calls = static::$serializer->denormalize($data, Calls::class, null, ['groups' => ['data', 'more']]);
        $normalized = static::$serializer->normalize($calls, null, ['groups' => ['data', 'more']]);
        $this->assertEquals($data, $normalized);
    }

    public function testMethodWithArrayArgument(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
            'data2_id' => 3886,
            'data2_name' => 'Peter Parker',
        ];

        $calls = static::$serializer->denormalize($data, Calls::class, null, ['groups' => ['data', 'more']]);
        $normalized = static::$serializer->normalize($calls, null, ['groups' => ['data', 'more']]);
        $this->assertEquals($data, $normalized);
    }

    public function testNormalizerDependency(): void
    {
        $data = static::$serializer->normalize(new DependencyInNormalizer());
        $this->assertTrue($data['example']);
    }

    public function testMissingNormalizerDependencyThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Argument logger of type Psr\Log\LoggerInterface in method Zolex\VOM\Test\Fixtures\DependencyInNormalizerMissing::normalizeData() can not be injected. Did you forget to configure it as a method dependency?');
        static::$serializer->denormalize([], DependencyInNormalizerMissing::class);
    }

    public function testDenormalizerDependency(): void
    {
        $model = static::$serializer->denormalize(['type' => 'something', 'format' => '123'], DependencyInDenormalizer::class);
        $this->assertTrue($model->example);
        $this->assertEquals('something', $model->type);
        $this->assertEquals('123', $model->format);
    }

    public function testMissingDenormalizerDependencyThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Argument logger of type Psr\Log\LoggerInterface in method Zolex\VOM\Test\Fixtures\DependencyInDenormalizerMissing::denormalizeData() can not be injected. Did you forget to configure it as a method dependency?');
        static::$serializer->denormalize([], DependencyInDenormalizerMissing::class);
    }

    public function testConstructorDependency(): void
    {
        $model = static::$serializer->denormalize(['type' => 'something', 'format' => '123'], DependencyInConstructor::class);
        $this->assertTrue($model->example);
        $this->assertEquals('something', $model->type);
        $this->assertEquals('123', $model->format);
    }

    public function testMissingConstructorDependencyThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Argument logger of type Psr\Log\LoggerInterface in method Zolex\VOM\Test\Fixtures\DependencyInConstructorMissing::__construct() can not be injected. Did you forget to configure it as a method dependency?');
        static::$serializer->denormalize([], DependencyInConstructorMissing::class);
    }

    public function testConstructorPropertyPromotionDependency(): void
    {
        $model = static::$serializer->denormalize(['type' => 'something', 'format' => '123'], DependencyInConstructorPropertyPromotion::class);
        $this->assertTrue($model->getExample());
        $this->assertEquals('something', $model->type);
        $this->assertEquals('123', $model->format);
    }

    public function testFactoryDependency(): void
    {
        $model = static::$serializer->denormalize(['type' => 'something', 'format' => '123'], DependencyInFactory::class);
        $this->assertTrue($model->example);
        $this->assertEquals('something', $model->type);
        $this->assertEquals('123', $model->format);
    }

    public function testMissingFactoryDependencyThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Argument logger of type Psr\Log\LoggerInterface in method Zolex\VOM\Test\Fixtures\DependencyInFactoryMissing::create() can not be injected. Did you forget to configure it as a method dependency?');
        static::$serializer->denormalize([], DependencyInFactoryMissing::class);
    }

    public function testGoodDenormalizer(): void
    {
        $calls = new Calls();
        $normalized = static::$serializer->normalize($calls, null, ['groups' => ['good']]);
        $this->assertEquals(['good_string' => 'string'], $normalized);
    }

    public function testBadDenormalizerThrowsException(): void
    {
        $calls = new Calls();
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer Zolex\VOM\Test\Fixtures\Calls::getBadString() without accessor must return an array.');
        static::$serializer->normalize($calls, null, ['groups' => ['bad']]);
    }

    public function testStaticNormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer method Zolex\VOM\Test\Fixtures\StaticNormalizer::staticNormalizer() should not be static.');
        static::$serializer->denormalize([], StaticNormalizer::class);
    }

    public function testPrivateNormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer method Zolex\VOM\Test\Fixtures\PrivateNormalizer::privateNormalizer() must be public.');
        static::$serializer->denormalize([], PrivateNormalizer::class);
    }

    public function testStaticDenormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer method Zolex\VOM\Test\Fixtures\StaticDenormalizer::staticDenormalizer() should not be static.');
        static::$serializer->denormalize([], StaticDenormalizer::class);
    }

    public function testPrivateDenormalizerThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer method Zolex\VOM\Test\Fixtures\PrivateDenormalizer::privateDenormalizer() must be public.');
        static::$serializer->denormalize([], PrivateDenormalizer::class);
    }

    public function testMethodCallsOnInvalidDenormalizer(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Denormalizer on "Zolex\VOM\Test\Fixtures\CallsOnInvalidDenormalizer::bla()" cannot be added.');
        static::$serializer->denormalize([], CallsOnInvalidDenormalizer::class);
    }

    public function testMethodCallsOnInvalidNormalizer(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Normalizer on "Zolex\VOM\Test\Fixtures\CallsOnInvalidNormalizer::blubb()" cannot be added.');
        static::$serializer->normalize(new CallsOnInvalidNormalizer());
    }

    public function testMethodCallWithArray(): void
    {
        $result = static::$serializer->denormalize([
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
        $result = static::$serializer->denormalize([
            'thing' => (object) [
                'name' => 'fly thing',
            ],
        ], CallWithObject::class);

        $this->assertInstanceOf(CallWithObject::class, $result);
        $this->assertEquals('fly thing', $result->getName());
    }

    public function testMethodCallWithCompatibleClass(): void
    {
        $result = static::$serializer->denormalize([
            'thing' => new CallWithObjectInput(name: 'hit thing'),
        ], CallWithObject::class);

        $this->assertInstanceOf(CallWithObject::class, $result);
        $this->assertEquals('hit thing', $result->getName());
    }

    public function testMethodCallWithIncompatibleClass(): void
    {
        $result = static::$serializer->denormalize([
            'thing' => new Address(),
        ], CallWithObject::class);

        $this->assertInstanceOf(CallWithObject::class, $result);
        $this->assertNull($result->getName());
    }

    public function testMethodCallWithUnsupportedArrayThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Only scalars are allowed for method call Zolex\VOM\Test\Fixtures\CallWithUnsupportedArray::setArray(). Consider using collection attributes.');
        static::$serializer->denormalize([], CallWithUnsupportedArray::class);
    }

    public function testMethodCallWithUnsupportedClassThrowsException(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('Only builtin types are supported for method call Zolex\VOM\Test\Fixtures\CallWithUnsupportedClass::setObject().');
        static::$serializer->denormalize([], CallWithUnsupportedClass::class);
    }

    public function testDenormalizerWithGroups(): void
    {
        $data = [
            'id' => 42,
            'name' => 'Peter Enis',
            'data2_id' => 1337,
            'data2_name' => 'Peter Parker',
        ];

        $calls = static::$serializer->denormalize($data, Calls::class, null, ['groups' => ['data']]);
        $this->assertEquals(['id' => 42, 'name' => 'Peter Enis'], $calls->normalizeData());
        $this->assertEquals([], $calls->getMoreData());

        $calls = static::$serializer->denormalize($data, Calls::class, null, ['groups' => ['more']]);
        $normalizedData = static::$serializer->normalize($calls, null, ['groups' => ['data']]);
        $this->assertEquals([], $normalizedData);
        $normalizedMore = static::$serializer->normalize($calls, null, ['groups' => ['more']]);
        $this->assertEquals(['data2_id' => 1337, 'data2_name' => 'Peter Parker'], $normalizedMore);

        $calls = static::$serializer->denormalize($data, Calls::class);
        $this->assertEquals(['id' => 42, 'name' => 'Peter Enis'], $calls->normalizeData());
        $this->assertEquals(['data2_id' => 1337, 'data2_name' => 'Peter Parker'], $calls->getMoreData());
    }
}
