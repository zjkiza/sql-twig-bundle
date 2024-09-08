<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Exception;

use Zjk\SqlTwig\Exception\Message\ExceptionMessage;

class ExecuteException extends \RuntimeException
{
    use ExceptionMessage;
}
