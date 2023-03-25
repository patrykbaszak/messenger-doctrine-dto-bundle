<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;

trait GetTargetEntity
{
    protected function getTargetEntity(object $dto): string
    {
        $attributes = (new \ReflectionClass($dto))->getAttributes(TargetEntity::class);

        if (0 === count($attributes)) {
            throw new \LogicException(sprintf('No %s attribute found on %s', TargetEntity::class, get_class($dto)));
        }

        return $attributes[0]->newInstance()->entityClass;
    }
}
