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
use Zolex\VOM\Test\Fixtures\ScenarioConstructorArguments;
use Zolex\VOM\Test\Fixtures\ScenarioConstructorPropertyPromotion;
use Zolex\VOM\Test\Fixtures\ScenarioDenormalizer;
use Zolex\VOM\Test\Fixtures\ScenarioFactory;
use Zolex\VOM\Test\Fixtures\ScenarioNormalizer;
use Zolex\VOM\Test\Fixtures\ScenarioNormalizerWithGroups;
use Zolex\VOM\Test\Fixtures\ScenarioProperties;
use Zolex\VOM\Test\Functional\Standard\VersatileObjectMapperTestCase;

/**
 * @mixin VersatileObjectMapperTestCase
 */
class ScenariosTestCase extends TestCase
{
    public function testScenarioProperties(): void
    {
        $defaultScenario = [
            'street' => 'default street',
            'houseNo' => '1337 a',
        ];

        $default = static::$serializer->denormalize($defaultScenario, ScenarioProperties::class);
        $this->assertInstanceOf(ScenarioProperties::class, $default);
        $this->assertEquals($defaultScenario['street'], $default->getStreet());
        $this->assertEquals($defaultScenario['houseNo'], $default->getHouseNo());

        $customScenario = [
            'address' => [
                'street_name' => 'custom street',
                'house_number' => '42',
            ],
        ];

        $custom = static::$serializer->denormalize($customScenario, ScenarioProperties::class, null, ['scenario' => 'custom']);
        $this->assertInstanceOf(ScenarioProperties::class, $custom);
        $this->assertEquals($customScenario['address']['street_name'], $custom->getStreet());
        $this->assertEquals($customScenario['address']['house_number'], $custom->getHouseNo());
    }

    public static function getMethodTypes(): array
    {
        return [
            [ScenarioDenormalizer::class],
            [ScenarioFactory::class],
            [ScenarioConstructorArguments::class],
            [ScenarioConstructorPropertyPromotion::class],
        ];
    }

    /**
     * @dataProvider getMethodTypes
     */
    public function testScenarioMethods(string $class): void
    {
        $scenarioOne = [
            'firstname' => 'Peter',
            'lastname' => 'Parker',
        ];

        $one = static::$serializer->denormalize($scenarioOne, $class, null, ['scenario' => 'one']);
        $this->assertInstanceOf($class, $one);
        $this->assertEquals($scenarioOne['firstname'], $one->getFirstName());
        $this->assertEquals($scenarioOne['lastname'], $one->getLastName());

        $scenarioTwo = [
            'name' => [
                'firstname' => 'Uncle',
                'surname' => 'Ben',
            ],
        ];

        $two = static::$serializer->denormalize($scenarioTwo, $class, null, ['scenario' => 'two']);
        $this->assertInstanceOf($class, $two);
        $this->assertEquals($scenarioTwo['name']['firstname'], $two->getFirstName());
        $this->assertEquals($scenarioTwo['name']['surname'], $two->getLastName());
    }

    public function testScenarioNormalizer(): void
    {
        $one = static::$serializer->normalize(new ScenarioNormalizer(), null, ['scenario' => 'one']);
        $this->assertEquals(['scenario' => 'one', 'additional' => 1], $one);

        $two = static::$serializer->normalize(new ScenarioNormalizer(), null, ['scenario' => 'two']);
        $this->assertEquals(['scenario' => 'two', 'additional' => 2], $two);
    }

    public function testScenarioNormalizerWithGroups(): void
    {
        $oneA = static::$serializer->normalize(new ScenarioNormalizerWithGroups(), null, ['scenario' => 'one', 'groups' => ['a']]);
        $this->assertEquals(['scenario' => 'oneA', 'additional' => 1], $oneA);

        $oneB = static::$serializer->normalize(new ScenarioNormalizerWithGroups(), null, ['scenario' => 'one', 'groups' => ['b']]);
        $this->assertEquals(['scenario' => 'oneB', 'additional' => 1], $oneB);

        $twoA = static::$serializer->normalize(new ScenarioNormalizerWithGroups(), null, ['scenario' => 'two', 'groups' => ['a']]);
        $this->assertEquals(['scenario' => 'twoA', 'additional' => 2], $twoA);

        $twoB = static::$serializer->normalize(new ScenarioNormalizerWithGroups(), null, ['scenario' => 'two', 'groups' => ['b']]);
        $this->assertEquals(['scenario' => 'twoB', 'additional' => 2], $twoB);
    }
}
