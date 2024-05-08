<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Contract;

interface ExceptionInterface
{
    /**
     * @return static
     */
    public static function formatted(string $message, string $exceptionMessage, bool $isDebug): self;
}
