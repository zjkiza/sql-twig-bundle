<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Exception;

use Zjk\SqlTwig\Contract\ExceptionInterface;
use Zjk\SqlTwig\Exception\Message\ExceptionMessage;

class SyntaxErrorException extends \Exception implements ExceptionInterface
{
    use ExceptionMessage;
}
