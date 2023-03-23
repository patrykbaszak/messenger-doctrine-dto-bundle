<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MessengerDoctrineDTOPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        //
    }
}
