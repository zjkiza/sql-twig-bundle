<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\Resources;

use Doctrine\Bundle\DoctrineBundle\Middleware\DebugMiddleware;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;
use Zjk\SqlTwig\Tests\Resources\App\ZJKizaSqlTwigTestKernel;

class KernelTestCase extends SymfonyKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return ZJKizaSqlTwigTestKernel::class;
    }

    protected function clearLoggedQuery(): void
    {
        \Closure::bind(function (): void {
            $this->debugDataHolder->reset();
        }, $this->getDebugMiddleware(), DebugMiddleware::class)();
    }

    /**
     * @return string[]
     */
    protected function getLoggedQuery(): array
    {
        $records = \Closure::bind(
            fn (): array => $this->debugDataHolder->getData(),
            $this->getDebugMiddleware(),
            DebugMiddleware::class
        )();

        $result = [];

        \array_walk_recursive($records, static function (string $value, string $key) use (&$result): void {
            if ('sql' === $key) {
                $result[] = $value;
            }
        });

        return $result;
    }

    private function getDebugMiddleware(): DebugMiddleware
    {
        /** @var Configuration $configuration */
        $configuration = $this->getContainer()->get(Connection::class)->getConfiguration(); // @phpstan-ignore-line

        return \array_values(
            \array_filter(
                $configuration->getMiddlewares(),
                static fn (Middleware $middleware): bool => $middleware instanceof DebugMiddleware
            )
        )[0];
    }
}
