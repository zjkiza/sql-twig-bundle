<?php

declare(strict_types=1);

namespace Zjk\SqlTwig;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zjk\SqlTwig\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class ZJKizaSqlTwigBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new Extension();
    }
}
