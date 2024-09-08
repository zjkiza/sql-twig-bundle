<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Contract;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Driver\Exception as ExceptionDriver;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\TransactionIsolationLevel;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

interface SqlTwigInterface
{
    /**
     * @param array<string, mixed> $args
     * @param array<string, int>   $types
     *
     * @throws ExceptionDriver
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function executeQuery(string $queryPath, array $args = [], array $types = [], ?QueryCacheProfile $qcp = null): Result;

    /**
     * @param TransactionIsolationLevel::* $transactionIsolationLevel
     */
    public function transaction(\Closure $func, int $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result;
}
