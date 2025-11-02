<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\Functionality;

use Zjk\SqlTwig\Contract\SqlTwigInterface;
use Zjk\SqlTwig\Exception\RuntimeException;
use Zjk\SqlTwig\Tests\Resources\KernelTestCase;

final class ExceptionTest extends KernelTestCase
{
    private SqlTwigInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        /** @psalm-suppress PropertyTypeCoercion */
        $this->manager = $this->getContainer()->get(SqlTwigInterface::class);
    }

    public function testExpectExceptionWhenNoPathOrFileExists(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The query "@query/lorem_ipsum.sql.twig" cannot be executed. Could not find query source: @query/lorem_ipsum.sql.twig. Unable to find template "@query/lorem_ipsum.sql.twig" (looked into: /www/tests/Resources/App/query).');

        $this->manager->executeQuery('@query/lorem_ipsum.sql.twig')->fetchAllAssociative();
    }

    public function testExpectExceptionWhenExistTwigSyntaxError(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Query source @query/twig_syntax_error.sql.twig contains Twig syntax error. Unknown "ifA" tag. Did you mean "if" in "@query/twig_syntax_error.sql.twig" at line 2?');

        $this->manager->executeQuery('@query/twig_syntax_error.sql.twig')->fetchAllAssociative();
    }

    public function testExpectExceptionWhenVariableNotPassed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The query "@query/variable_not_passed.sql.twig" cannot be executed. Query source @query/variable_not_passed.sql.twig contains unknown exception occurred. Variable "title" does not exist in "@query/variable_not_passed.sql.twig" at line 2.');

        $this->manager->executeQuery('@query/variable_not_passed.sql.twig')->fetchAllAssociative();
    }
}
