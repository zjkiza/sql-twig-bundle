<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\Functionality;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\TransactionIsolationLevel;
use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\Tests\Resources\KernelTestCase;

final class ExecuteQueryTest extends KernelTestCase
{
    private SqlTwigInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        /** @psalm-suppress PropertyTypeCoercion */
        $this->manager = $this->getContainer()->get(SqlTwigInterface::class);
    }

    public function testWithOutTwig(): void
    {
        $response = $this->manager->executeQuery('@query/media_id_title.sql.twig')
            ->fetchAllAssociative();

        $this->assertSame([
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684a',
                'title' => 'Title video 1',
            ],
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
                'title' => 'Title video 2',
            ],
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684c',
                'title' => 'Title video 3',
            ],
        ], $response);
    }

    public function testConditionWithoutTitle(): void
    {
        $response = $this->manager->executeQuery('@query/media_condition.sql.twig', [
            'title' => false,
            'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
        ])->fetchAllAssociative();

        $this->assertSame([
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
            ],
        ], $response);
    }

    public function testConditionWithTitle(): void
    {
        $response = $this->manager->executeQuery('@query/media_condition.sql.twig', [
            'title' => true,
            'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
        ])->fetchAllAssociative();

        $this->assertSame([
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
                'title' => 'Title video 2',
            ],
        ], $response);
    }

    public function testConditionWithUserAndMediaIds(): void
    {
        $response = $this->manager->executeQuery('@query/media_with_user.sql.twig', [
            'user' => true,
            'ids' => ['60b16643-d5e0-468a-8823-499fcf07684a', '60b16643-d5e0-468a-8823-499fcf07684b'],
        ], [
            'ids' => ArrayParameterType::STRING,
        ])->fetchAllAssociative();

        $this->assertSame([
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684a',
                'user_name' => 'User 1',
            ],
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
                'user_name' => 'User 2',
            ],
        ], $response);
    }

    public function testTransaction(): void
    {
        $this->clearLoggedQuery();

        $result = $this->manager->transaction(
            static fn (SqlTwigInterface $manager): Result => $manager->executeQuery('@query/media_id_title.sql.twig'),
            TransactionIsolationLevel::READ_UNCOMMITTED
        );

        \assert($result instanceof Result);

        $response = $result->fetchAllAssociative();

        $this->assertSame([
            'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED',
            '"START TRANSACTION"',
            'SELECT id, title FROM media;',
            '"COMMIT"',
            'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ',
        ], $this->getLoggedQuery());

        $this->assertSame([
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684a',
                'title' => 'Title video 1',
            ],
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684b',
                'title' => 'Title video 2',
            ],
            [
                'id' => '60b16643-d5e0-468a-8823-499fcf07684c',
                'title' => 'Title video 3',
            ],
        ], $response);
    }

    public function testExecuteQueryWithTempTable(): void
    {
        $this->manager->registerTempTable('@query/create_temp_table.sql.twig');

        $response = $this->manager->executeQueryWithTempTable(
            '@query/execute_with_tmp_table.sql.twig'
        )->fetchAllAssociative();

        $this->assertSame([
            [
                'id' => '87101abb-4e71-427a-a433-5ea7c253e56f',
            ],
            [
                'id' => 'd6dc85b9-58e1-407b-97d4-53af658e1e90',
            ],
        ], $response);

    }
}
