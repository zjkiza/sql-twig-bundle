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
use Twig\Error\RuntimeError;
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
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
final class SqlTwig implements SqlTwigInterface
{
    // CREATE TEMPORARY TABLE [IF NOT EXISTS] table_name
    private const REGEX_FIND_TEMP_TABLE_NAME = '/CREATE\s+TEMPORARY\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?([a-zA-Z0-9_]+)`?/i';

    private const TRANSACTION_ISOLATION_LEVEL = [
        'READ_UNCOMMITTED' => 1,
        'READ_COMMITTED' => 2,
        'REPEATABLE_READ' => 3,
        'SERIALIZABLE' => 4,
    ];

    /**
     * @var array<string, string>
     */
    private array $tempTables = [];

    public function __construct(
        private readonly Environment $environment,
        private readonly Connection $connection,
        private readonly bool $isDebug,
    ) {
    }

    /**
     * @throws RuntimeException
     * @throws Exception
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
            throw RuntimeException::formatted(\sprintf('The query "%s" cannot be executed. ', $queryPath), $exception->getMessage(), $this->isDebug, $exception);
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
            throw ExecuteException::formatted('Unable to execute transaction.', $exception->getMessage(), $this->isDebug, $exception);
        }
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws \Exception
     */
    public function registerTempTable(string $queryPath, array $context = []): void
    {
        $sql = \trim($this->getSql($queryPath, $context));

        if (false === \preg_match(self::REGEX_FIND_TEMP_TABLE_NAME, $sql, $matches)) {
            throw SyntaxErrorException::formatted(\sprintf('Could not detect TEMP table name in "%s"', $queryPath), '', $this->isDebug);
        }

        $tableName = $matches[1];

        if (\array_key_exists($tableName, $this->tempTables)) {
            throw RuntimeException::formatted(\sprintf('Temp table named "%s" already exists in query path "%s"', $tableName, $queryPath), '', $this->isDebug);
        }

        $this->tempTables[$tableName] = $queryPath;
    }

    /**
     * @throws RuntimeException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws RuntimeError
     * @throws LoaderError
     * @throws SyntaxError
     * @throws \Throwable
     */
    public function executeQueryWithTempTable(string $queryPath, array $args = [], array $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        try {
            $this->ensureConnectionIsAlive();

            return $this->executeQuery($queryPath, $args, $types, $qcp);
        } catch (\Exception $exception) {

            if (\str_contains($exception->getMessage(), "Can't reopen table")) {
                $this->recreateTempTables();

                return $this->executeQuery($queryPath, $args, $types, $qcp);
            }

            throw RuntimeException::formatted(\sprintf('The query with tmp table "%s" cannot be executed. ', $queryPath), $exception->getMessage(), $this->isDebug, $exception);
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
            /** @var ?Result $result */
            $result = $func($this);

            $this->connection->commit();
        } catch (\Exception $exception) {
            $this->connection->rollBack();
            throw RuntimeException::formatted('A rollback of the sql query has been executed.', $exception->getMessage(), $this->isDebug, $exception);
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
            throw LoaderErrorException::formatted(\sprintf('Could not find query source: %s. ', $queryPath), $exception->getMessage(), $this->isDebug, $exception);
        } catch (SyntaxError $exception) {
            throw SyntaxErrorException::formatted(\sprintf('Query source %s contains Twig syntax error. ', $queryPath), $exception->getMessage(), $this->isDebug, $exception);
        } catch (\Exception $exception) {
            throw RuntimeException::formatted(\sprintf('Query source %s contains unknown exception occurred. ', $queryPath), $exception->getMessage(), $this->isDebug, $exception);
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

    /**
     * @throws RuntimeException
     * @throws \Throwable
     */
    private function ensureConnectionIsAlive(): void
    {
        try {
            if ($this->connection->isConnected()) {
                // Ping to verify that the connection is indeed active
                $this->connection->executeQuery('SELECT 1');

                return;
            }

        } catch (\Throwable) {
            // If SELECT 1 fails — the connection is dead
        }

        /**
         * For support DBAL 3.x.
         *
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore-next-line
         */
        if (\method_exists($this->connection, 'connect') && (new \ReflectionMethod($this->connection, 'connect'))->isPublic()) {
            $this->connection->connect(); // @phpstan-ignore-line
            $this->recreateTempTables();

            return;
        }

        /**
         * For support DBAL 4.x.
         *
         * @phpstan-ignore-next-line
         */
        if (\method_exists($this->connection, 'getNativeConnection')) {
            $this->connection->getNativeConnection();
        }

        $this->connection->executeQuery('SELECT 1');
        $this->recreateTempTables();
    }

    /**
     * @throws RuntimeException
     */
    private function recreateTempTables(): void
    {
        foreach ($this->tempTables as $tableName => $sqlPath) {
            $this->createTempTable($tableName, $sqlPath);
        }
    }

    /**
     * @throws RuntimeException
     */
    private function createTempTable(string $tableName, string $sqlPath): void
    {
        try {
            $this->connection->executeStatement("DROP TEMPORARY TABLE IF EXISTS `$tableName`");
            $this->executeQuery($sqlPath);
        } catch (Exception $exception) {
            throw RuntimeException::formatted(\sprintf('Failed to recreate TEMP table "%s" with path "%s" ', $tableName, $sqlPath), $exception->getMessage(), $this->isDebug, $exception);
        }
    }
}
