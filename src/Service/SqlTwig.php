<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Service;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\TransactionIsolationLevel;
use Doctrine\DBAL\Types\Type;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\Exception\ExecuteException;
use Zjk\SqlTwig\Exception\LoaderErrorException;
use Zjk\SqlTwig\Exception\RuntimeException;
use Zjk\SqlTwig\Exception\SyntaxErrorException;

/**
 * @psalm-type WrapperParameterType = string|Type|ParameterType|ArrayParameterType
 * @psalm-type WrapperParameterTypeArray = array<int<0, max>, WrapperParameterType>|array<string, WrapperParameterType>
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SqlTwig implements SqlTwigInterface
{
    private const TRANSACTION_ISOLATION_LEVEL = [
        'READ_UNCOMMITTED' => 1,
        'READ_COMMITTED' => 2,
        'REPEATABLE_READ' => 3,
        'SERIALIZABLE' => 4,
    ];

    public function __construct(
        private readonly Environment $environment,
        private readonly Connection $connection,
        private readonly bool $isDebug,
    ) {
    }

    public function executeQuery(string $queryPath, array $args = [], array $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        try {
            return $this->connection->executeQuery(
                $this->getSql($queryPath, $args),
                $args,
                $types,
                $qcp
            );
        } catch (\Exception $exception) {
            throw RuntimeException::formatted(\sprintf('The query "%s" cannot be executed. ', $queryPath), $exception->getMessage(), $this->isDebug);
        }
    }

    /**
     * @throws RuntimeException
     */
    public function transaction(\Closure $func, int|TransactionIsolationLevel $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result
    {
        if (\is_int($transactionIsolationLevel)) {
            $this->validationTransactionIsolationLevel($transactionIsolationLevel);
        }

        try {
            return $this->dbalTransaction($func, $transactionIsolationLevel);
        } catch (Exception $exception) {
            throw ExecuteException::formatted('Unable to execute transaction.', $exception->getMessage(), $this->isDebug);
        }
    }

    /**
     * @throws Exception
     * @throws RuntimeException
     */
    private function dbalTransaction(\Closure $func, int|TransactionIsolationLevel $transactionIsolationLevel): ?Result
    {
        $previousIsolationLevel = $this->connection->getTransactionIsolation();

        /**
         * @psalm-suppress PossiblyInvalidArgument
         *
         * @phpstan-ignore-next-line
         *
         * Using `int|TransactionIsolationLevel` ensures this method remains compatible with both older
         * and newer versions of the library, enabling smooth transitions and backward compatibility.
         */
        $this->connection->setTransactionIsolation($transactionIsolationLevel);
        $this->connection->beginTransaction();

        try {
            $result = null;
            $result = $func($this);

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw RuntimeException::formatted('A rollback of the sql query has been executed.', $exception->getMessage(), $this->isDebug);
        } finally {
            $this->connection->setTransactionIsolation($previousIsolationLevel);
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $args
     *
     * @throws \Exception
     */
    private function getSql(string $queryPath, array $args): string
    {
        try {
            return $this->environment->render($queryPath, $args);
        } catch (LoaderError $exception) {
            throw LoaderErrorException::formatted(\sprintf('Could not find query source: %s. ', $queryPath), $exception->getMessage(), $this->isDebug);
        } catch (SyntaxError $exception) {
            throw SyntaxErrorException::formatted(\sprintf('Query source %s contains Twig syntax error. ', $queryPath), $exception->getMessage(), $this->isDebug);
        } catch (\Exception $exception) {
            throw RuntimeException::formatted(\sprintf('Query source %s contains unknown exception occurred. ', $queryPath), $exception->getMessage(), $this->isDebug);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function validationTransactionIsolationLevel(int $transactionIsolationLevel): void
    {
        /** @psalm-suppress DocblockTypeContradiction */
        if (!\in_array($transactionIsolationLevel, self::TRANSACTION_ISOLATION_LEVEL, true)) {
            throw new RuntimeException('Transaction isolation level it\'s out the allowed range [1-4].', 422);
        }
    }
}
