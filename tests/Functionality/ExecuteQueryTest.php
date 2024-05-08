<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\Functionality;

use Doctrine\DBAL\ArrayParameterType;
use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\Tests\Resources\KernelTestCase;

final class ExecuteQueryTest extends KernelTestCase
{
    private SqlTwigInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

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
}
