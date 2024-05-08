<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Exception\Message;

trait ExceptionMessage
{
    /**
     * @phpstan-return self
     */
    public static function formatted(string $message, string $exceptionMessage, bool $isDebug): self
    {
        $formattedMessage = $message;

        if ($isDebug) {
            $formattedMessage .= $exceptionMessage;
        }

        return new self($formattedMessage);
    }
}
