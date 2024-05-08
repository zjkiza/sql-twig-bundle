<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\Resources\App;

use DAMA\DoctrineTestBundle\DAMADoctrineTestBundle;
use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Kernel;
use Zjk\SqlTwig\Tests\Resources\Fixtures\TestFixtures;
use Zjk\SqlTwig\ZJKizaSqlTwigBundle;
use Ramsey\Uuid\Doctrine\UuidType;

final class ZJKizaSqlTwigTestKernel extends Kernel
{
    use MicroKernelTrait;

    public function getProjectDir(): string
    {
        return \realpath(__DIR__.'/..'); // @phpstan-ignore-line
    }

    public function registerBundles(): iterable
    {
        return [
            new TwigBundle(),
            new FrameworkBundle(),
            new DoctrineBundle(),
            new DoctrineFixturesBundle(),
            new DAMADoctrineTestBundle(),
            new ZJKizaSqlTwigBundle(),
        ];
    }

    public function configureContainer(ContainerConfigurator $container): void
    {
        $container->services()->set(TestFixtures::class)->tag('doctrine.fixture.orm')->autowire()->autoconfigure();

        $container->extension('framework', [
            'secret'        => 'test',
            'test'          => true,
            'property_info' => [
                'enabled' => true,
            ],
        ]);

        $container->extension('twig', [
            'paths' => [
                '%kernel.project_dir%/App/query' => 'query',
            ],
        ]);

        $container->extension('doctrine', [
            'dbal' => [
                'driver' => 'pdo_mysql',
                'url'    => 'mysql://developer:developer@mysql_bundle_2/developer',
                'use_savepoints' => true,
                'types' => [
                    'uuid' => UuidType::class,
                ],
            ],
            'orm'  => [
                'auto_generate_proxy_classes' => true,
                'naming_strategy'             => 'doctrine.orm.naming_strategy.underscore_number_aware',
                'auto_mapping'                => true,
                'enable_lazy_ghost_objects'   => true,
                'report_fields_where_declared' => true,
                'mappings'                    => [
                    'Tests' => [
                        'is_bundle' => false,
                        'type'      => 'attribute',
                        'dir'       => __DIR__.'/Entities',
                        'prefix'    => 'Zjk\SqlTwig\Tests\Resources\App',
                    ],
                ],
            ],
        ]);
    }
}
