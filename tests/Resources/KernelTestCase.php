<?php

declare(strict_types=1);

namespace Zjk\SqlTwig\Tests\Resources;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase as SymfonyKernelTestCase;
use Zjk\SqlTwig\Tests\Resources\App\ZJKizaSqlTwigTestKernel;

class KernelTestCase extends SymfonyKernelTestCase
{
    protected static function getKernelClass(): string
    {
        return ZJKizaSqlTwigTestKernel::class;
    }
}
