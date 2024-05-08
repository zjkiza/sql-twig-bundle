<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\DependencyInjection\Reference;
use Twig\Environment;
use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\DependencyInjection\Extension;
use Zjk\SqlTwig\Service\SqlTwig;

final class ExtensionTest extends AbstractExtensionTestCase
{
    public function testDefaultsServiceConfig(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(SqlTwig::class, SqlTwig::class);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(SqlTwig::class, 0, new Reference(Environment::class));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(SqlTwig::class, 1, new Reference('doctrine.dbal.default_connection'));
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(SqlTwig::class, 2, '%kernel.debug%');

        $this->assertContainerBuilderHasAlias(SqlTwigInterface::class, SqlTwig::class);
    }

    protected function getContainerExtensions(): array
    {
        return [
            new Extension(),
        ];
    }
}
