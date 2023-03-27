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

    protected function getTargetEntityIfIsDeclared(string|\ReflectionProperty|\ReflectionParameter $property): ?string
    {
        if (is_string($property)) {
            return null;
        }

        $targetEntity = $property->getAttributes(TargetEntity::class);

        if (empty($targetEntity)) {
            $reflectionType = $property->getType();

            if ($reflectionType instanceof \ReflectionNamedType) {
                $type = $reflectionType->getName();

                if (class_exists($type)) {
                    $reflectionClass = new \ReflectionClass($type);
                    $entity = $reflectionClass->getAttributes(Entity::class);
                    $targetEntity = $reflectionClass->getAttributes(TargetEntity::class);

                    if (empty($entity) && empty($targetEntity)) {
                        return null;
                    }

                    return $type;
                }
            }

            return null;
        }

        return $targetEntity[0]->newInstance()->entityClass;
    }
}
