<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 *
 * @author Patryk Baszak <patryk.baszak@gmail.com>
 */
class MessengerDoctrineDTOBundle extends Bundle
{
    public const ALIAS = 'messenger_doctrine_dto';

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DependencyInjection\MessengerDoctrineDTOPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return $this->extension ??= new DependencyInjection\MessengerDoctrineDTOExtension();
    }
}
