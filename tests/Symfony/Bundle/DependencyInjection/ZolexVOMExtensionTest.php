<?php

namespace Zolex\VOM\Test\Symfony\Bundle\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Zolex\VOM\Symfony\Bundle\DependencyInjection\ZolexVOMExtension;

class ZolexVOMExtensionTest extends TestCase
{
    use ProphecyTrait;

    public function getExtensionSetup()
    {
        yield [];
    }

    public function testLoadExtension(): void
    {
        $configs = [];
        $parameterBag = new ParameterBag();
        $builder = $this->prophesize(ContainerBuilder::class);

        $builder->has(Argument::is('api_platform.serializer.context_builder'))
            ->willReturn(true)
            ->shouldBeCalledOnce();

        $builder->hasExtension(Argument::any())->willReturn(false);
        $builder->fileExists(Argument::any())->willReturn(false);
        $builder->getParameterBag()->willReturn($parameterBag);
        $builder->setAlias(
            Argument::type('string'),
            Argument::type('string'),
        )->willReturn(new Alias(''));

        $builder->setAlias(
            Argument::type('string'),
            Argument::type(Alias::class),
        )->willReturnArgument(1);

        $builder->setDefinition(
            Argument::is('zolex_vom.serializer.groups_context_builder'),
            Argument::type(Definition::class),
        )->willReturnArgument(1)
            ->shouldBeCalledOnce();

        $builder->setDefinition(
            Argument::any(),
            Argument::type(Definition::class),
        )->willReturnArgument(1);

        $builder->getReflectionClass(Argument::type('string'))
            ->will(function ($args) {
                return new \ReflectionClass($args[0]);
            });

        $builder->removeBindings(Argument::any())->will(function () {});

        $container = $builder->reveal();
        $extension = new ZolexVOMExtension();
        $extension->load($configs, $container);
        $extension->process($container);


    }
}