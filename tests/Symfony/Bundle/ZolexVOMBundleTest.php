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

namespace Zolex\VOM\Test\Symfony\Bundle;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zolex\VOM\Test\Fixtures\Person;
use Zolex\VOM\Test\Fixtures\Thing;

class ZolexVOMBundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return ZolexVOMTestKernel::class;
    }

    public function testServicesAreRegistered(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        foreach (ZolexVOMTestKernel::SERVICES as $id => $class) {
            $this->assertTrue($container->has($id));
            $service = $container->get($id);
            $this->assertInstanceOf($class, $service);
        }
    }

    public function testNonDebugServicesAreRegistered(): void
    {
        $kernel = self::bootKernel(['environment' => 'prod', 'debug' => false]);
        $container = $kernel->getContainer();

        foreach (ZolexVOMTestKernel::NON_DEBUG_SERVICES as $id => $class) {
            $this->assertTrue($container->has($id));
            $service = $container->get($id);
            $this->assertInstanceOf($class, $service);
        }
    }

    public function testVomIntegratesWithSerializer(): void
    {
        $kernel = self::bootKernel();
        $container = $kernel->getContainer();

        $serializer = $container->get('serializer');
        $data = [
            'type' => 'person',
            'name' => [
                'firstname' => 'Peter',
                'lastname' => 'Parker',
            ],
        ];

        $person = $serializer->denormalize($data, Thing::class, null, ['vom' => true]);

        $this->assertInstanceOf(Person::class, $person);
        $this->assertEquals('Peter', $person->firstname);
        $this->assertEquals('Parker', $person->lastname);
    }
}
