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
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\Exception\LogicException;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorFromClassMetadata;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer as SymfonyObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UnwrappingDenormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\TypeInfo\TypeResolver\TypeResolver;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Serializer\Normalizer\ObjectNormalizer;
use Zolex\VOM\Serializer\VersatileObjectMapper;
use Zolex\VOM\Test\Fixtures\ExpressionLanguageModel;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
abstract class ExpressionLanguageTestCase extends TestCase
{
    public function testDenormalizeWithExpression(): void
    {
        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
        ];

        /** @var ExpressionLanguageModel $model */
        $model = static::$serializer->denormalize($data, ExpressionLanguageModel::class);
        $this->assertSame('John Doe', $model->fullName);
    }

    public function testNormalizeWithExpression(): void
    {
        $model = new ExpressionLanguageModel();
        $model->age = 30;

        $normalized = static::$serializer->normalize($model);
        $this->assertSame(60, $normalized['doubleAge']);
    }

    public function testDenormalizeExpressionWithMissingPackageThrows(): void
    {
        $vom = $this->createVomWithoutExpressionLanguage();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/expression-language is required to use the "denormalize" expression option.');
        $vom->denormalize(['first_name' => 'John', 'last_name' => 'Doe'], ExpressionLanguageModel::class);
    }

    public function testNormalizeExpressionWithMissingPackageThrows(): void
    {
        $vom = $this->createVomWithoutExpressionLanguage();

        $model = new ExpressionLanguageModel();
        $model->age = 30;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('symfony/expression-language is required to use the "normalize" expression option.');
        $vom->normalize($model);
    }

    private function createVomWithoutExpressionLanguage(): VersatileObjectMapper
    {
        $metadataFactory = new ModelMetadataFactory(TypeResolver::create());
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
        $classDiscriminatorResolver = new ClassDiscriminatorFromClassMetadata($classMetadataFactory);

        $objectNormalizer = new ObjectNormalizer(
            $metadataFactory,
            $propertyAccessor,
            $classMetadataFactory,
            $classDiscriminatorResolver,
            [],
            null,
            null,
        );

        $serializer = new Serializer(
            [
                new UnwrappingDenormalizer(),
                $objectNormalizer,
                new DateTimeNormalizer(),
                new JsonSerializableNormalizer(),
                new ArrayDenormalizer(),
                new SymfonyObjectNormalizer(),
            ],
            []
        );

        return new VersatileObjectMapper($serializer);
    }
}
