<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension as SymfonyExtension;

/**
 * Notice for symfony 8.1 support you need change Symfony\Component\HttpKernel\DependencyInjection\Extension
 * to  Symfony\Component\DependencyInjection\Extension\Extension.
 *
 * TODO : Add support for both, due to old versions
 *
 * @psalm-suppress InternalClass
 */
class Extension extends SymfonyExtension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }
}
