<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Service;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\TransactionIsolationLevel;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\Exception\ExecuteException;
use Zjk\SqlTwig\Exception\LoaderErrorException;
use Zjk\SqlTwig\Exception\RuntimeException;
use Zjk\SqlTwig\Exception\SyntaxErrorException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class SqlTwig implements SqlTwigInterface
{
    private const TRANSACTION_ISOLATION_LEVEL = [
        TransactionIsolationLevel::READ_UNCOMMITTED,
        TransactionIsolationLevel::READ_COMMITTED,
        TransactionIsolationLevel::REPEATABLE_READ,
        TransactionIsolationLevel::SERIALIZABLE,
    ];

    public function __construct(
        private readonly Environment $environment,
        private readonly Connection $connection,
        private readonly bool $isDebug
    ) {
    }

    /**
     * @throws RuntimeException
     */
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
    public function transaction(\Closure $func, int $transactionIsolationLevel = TransactionIsolationLevel::READ_COMMITTED): ?Result
    {
        $this->validationTransactionIsolationLevel($transactionIsolationLevel);

        try {
            return $this->dbalTransaction($func, $transactionIsolationLevel);
        } catch (Exception $exception) {
            throw ExecuteException::formatted('Unable to execute transaction.', $exception->getMessage(), $this->isDebug);
        }
    }

    /**
     * @param TransactionIsolationLevel::* $transactionIsolationLevel
     *
     * @throws Exception
     * @throws RuntimeException
     */
    private function dbalTransaction(\Closure $func, int $transactionIsolationLevel): ?Result
    {
        $previousIsolationLevel = $this->connection->getTransactionIsolation();

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
     * @param TransactionIsolationLevel::* $transactionIsolationLevel
     *
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
