<?php

namespace Zolex\VOM\Test\ApiPlatform\Serializer;

use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\HttpFoundation\Request;
use Zolex\VOM\ApiPlatform\Serializer\GroupsContextBuilder;
use Zolex\VOM\Metadata\Factory\ModelMetadataFactory;
use Zolex\VOM\Metadata\Factory\PropertyMetadataFactory;
use Zolex\VOM\Test\Fixtures\Person;

class GroupsContextBuilderTest extends TestCase
{
    use ProphecyTrait;

    public function testNoResourceClass(): void
    {
        $request = Request::create('/api/resource');

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([], $context);
    }

    public function testInitializesGroups(): void
    {
        $request = Request::create('/api/resource');

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'groups' => [],
        ], $context);
    }

    public function testRequestReplacesGroups(): void
    {
        $request = Request::create('/api/resource', 'GET', ['groups' => ['add', 'another']]);

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
                'groups' => ['existing'],
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'groups' => ['add', 'another'],
        ], $context);
    }

    public function testRequestPrependsBeforeStaticGroups(): void
    {
        $request = Request::create('/api/resource', 'GET', ['groups' => ['add']]);

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
                'static-groups' => ['existing'],
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'static-groups' => ['existing'],
            'groups' => ['add', 'existing'],
        ], $context);
    }

    public function testRequestStringIsConvertedToArray(): void
    {
        $request = Request::create('/api/resource', 'GET', ['groups' => 'add']);

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'groups' => ['add'],
        ], $context);
    }

    public function testStaticGroupsStringIsConvertedToArray(): void
    {
        $request = Request::create('/api/resource');

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
                'static-groups' => 'static',
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'static-groups' => ['static'],
            'groups' => ['static'],
        ], $context);
    }

    public function testPresetIsResolvedToGroups(): void
    {
        $request = Request::create('/api/resource', 'GET', ['groups' => 'preset-name']);

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'groups' => ['group-a', 'group-b'],
        ], $context);
    }

    public function testParentGroupsAreAdded(): void
    {
        $request = Request::create('/api/resource', 'GET', ['groups' => ['parent.group', 'another.child']]);

        /** @var GroupsContextBuilder $contextBuilder */
        $serializerContextBuilder = $this->prophesize(SerializerContextBuilderInterface::class);
        $serializerContextBuilder->createFromRequest(Argument::is($request), Argument::any(), Argument::any())
            ->willReturn([
                'resource_class' => Person::class,
            ]);

        $contextBuilder = new GroupsContextBuilder(
            $serializerContextBuilder->reveal(),
            new ModelMetadataFactory(new PropertyMetadataFactory()),
            'groups',
        );

        $context = $contextBuilder->createFromRequest($request, true);
        $this->assertEquals([
            'resource_class' => Person::class,
            'groups' => ['parent.group', 'another.child', 'parent', 'another'],
        ], $context);
    }
}
