<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Contract;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Driver\Exception as ExceptionDriver;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types\Type;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * @psalm-type WrapperParameterType = string|Type|ParameterType|ArrayParameterType
 * @psalm-type WrapperParameterTypeArray = array<int<0, max>, WrapperParameterType>|array<string, WrapperParameterType>
 */
interface SqlTwigInterface
{
    /**
     * @param array<string, mixed> $args
     *
     * @psalm-param WrapperParameterTypeArray $types
     *
     * @throws ExceptionDriver
     * @throws Exception
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function executeQuery(string $queryPath, array $args = [], array $types = [], ?QueryCacheProfile $qcp = null): Result;

    /**
     * @param int|TransactionIsolationLevel $transactionIsolationLevel
     *
     * This parameter supports two types for compatibility with different versions of doctrine/dbal:
     * - In doctrine/dbal versions < 4, the transaction isolation level is expected to be an integer,
     * corresponding to predefined constants (e.g., TransactionIsolationLevel::READ_COMMITTED as an int).
     * - In doctrine/dbal versions >= 4, the transaction isolation level is represented by the
     * TransactionIsolationLevel class, providing a more type-safe implementation.
     *
     * Using `int|TransactionIsolationLevel` ensures this method remains compatible with both older
     * and newer versions of the library, enabling smooth transitions and backward compatibility.
     */
    public function transaction(\Closure $func, int|TransactionIsolationLevel $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result;
}
