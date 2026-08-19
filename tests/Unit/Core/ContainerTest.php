<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Core\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

/**
 * Fixtures locales (définies dans ce fichier, chargées avec la classe de test).
 */
final class ContainerTestDependency
{
}

final class ContainerTestService
{
    public function __construct(public readonly ContainerTestDependency $dependency)
    {
    }
}

final class ContainerTest extends TestCase
{
    public function testAutowiresConstructorDependencies(): void
    {
        $container = new Container();

        $service = $container->get(ContainerTestService::class);

        self::assertInstanceOf(ContainerTestDependency::class, $service->dependency);
    }

    public function testServicesAreSingletons(): void
    {
        $container = new Container();

        self::assertSame(
            $container->get(ContainerTestService::class),
            $container->get(ContainerTestService::class)
        );
        self::assertSame(
            $container->get(ContainerTestDependency::class),
            $container->get(ContainerTestDependency::class)
        );
    }

    public function testClassWithoutConstructorIsResolved(): void
    {
        $container = new Container();

        self::assertInstanceOf(stdClass::class, $container->get(stdClass::class));
    }

    public function testRegisteredFactoryIsUsedAndCached(): void
    {
        $container = new Container();
        $container->set('stdClass', static fn (): stdClass => new stdClass());

        $first = $container->get('stdClass');
        $second = $container->get('stdClass');

        self::assertInstanceOf(stdClass::class, $first);
        self::assertSame($first, $second);
    }

    public function testUnknownClassThrows(): void
    {
        $container = new Container();

        $this->expectException(RuntimeException::class);
        $container->get(__NAMESPACE__ . '\\Inexistant');
    }
}
