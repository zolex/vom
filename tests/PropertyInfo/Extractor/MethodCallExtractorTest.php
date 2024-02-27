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

namespace Zolex\VOM\Test\PropertyInfo\Extractor;

use PHPUnit\Framework\TestCase;
use Zolex\VOM\Exception\InvalidArgumentException;
use Zolex\VOM\PropertyInfo\Extractor\MethodCallExtractor;
use Zolex\VOM\Test\Fixtures\Calls;
use Zolex\VOM\Test\Fixtures\CallsOnInvalidDenormalizer;

class MethodCallExtractorTest extends TestCase
{
    public function testMissingContextReturnsNull(): void
    {
        $extractor = new MethodCallExtractor();
        $types = $extractor->getTypes(Calls::class, 'name');
        $this->assertNull($types);
    }

    public function testMissingArgumentReturnsNull(): void
    {
        $class = new \ReflectionClass(Calls::class);
        $extractor = new MethodCallExtractor();
        $types = $extractor->getTypes(Calls::class, 'nonExistent', [
            'reflection_class' => $class,
            'reflection_method' => $class->getMethod('denormalizeData'),
        ]);

        $this->assertNull($types);
    }

    public function testWrongContextClassThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reflection class in context "Zolex\VOM\Test\Fixtures\CallsOnInvalidDenormalizer" does not match the given classname "Zolex\VOM\Test\Fixtures\Calls"');

        $class = new \ReflectionClass(CallsOnInvalidDenormalizer::class);
        $extractor = new MethodCallExtractor();
        $extractor->getTypes(Calls::class, 'name', [
            'reflection_class' => $class,
            'reflection_method' => $class->getMethod('bla'),
        ]);
    }
}
