<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils;

use Doctrine\ORM\Mapping\Entity;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;

trait GetTargetEntity
{
    /** @param class-string<object>|object $dto */
    protected function getTargetEntity(string|object $dto): string
    {
        $attributes = (new \ReflectionClass($dto))->getAttributes(TargetEntity::class);

        if (0 === count($attributes)) {
            $attributes = (new \ReflectionClass($dto))->getAttributes(Entity::class);
            if (!empty($attributes)) { // what means that the DTO is an entity
                return is_string($dto) ? $dto : get_class($dto);
            }

            throw new \LogicException(sprintf('No %s attribute found on %s.', TargetEntity::class, get_class($dto)));
        }

        return $attributes[0]->newInstance()->entityClass;
    }
}
