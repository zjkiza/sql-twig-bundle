<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Service;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\Exception\LoaderErrorException;
use Zjk\SqlTwig\Exception\RuntimeException;
use Zjk\SqlTwig\Exception\SyntaxErrorException;

final class SqlTwig implements SqlTwigInterface
{
    private Environment $environment;

    private Connection $connection;

    private bool $isDebug;

    public function __construct(Environment $environment, Connection $connection, bool $isDebug)
    {
        $this->environment = $environment;
        $this->connection = $connection;
        $this->isDebug = $isDebug;
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
}
