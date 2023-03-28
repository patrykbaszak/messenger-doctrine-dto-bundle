<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
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
        if (!empty($targetEntity)) {
            return $targetEntity[0]->newInstance()->entityClass;
        }

        $reflectionType = $property->getType();
        if (!$reflectionType instanceof \ReflectionNamedType) {
            return null;
        }

        $type = $reflectionType->getName();
        if (!class_exists($type)) {
            return null;
        }

        $reflectionClass = new \ReflectionClass($type);
        if ($this->hasClassEntityOrTargetEntityAttributes($reflectionClass)) {
            return $this->getTargetEntity($type);
        }

        return null;
    }

    /**
     * @return array{relationType: 'manyToMany'|'manyToOne'|'oneToMany'|'oneToOne', targetEntity: class-string|null}|null
     */
    protected function getTargetEntityAndRelationFromProperty(string|\ReflectionProperty|\ReflectionParameter $property): ?array
    {
        if (is_string($property)) {
            return null;
        }

        $reflectionType = $property->getType();
        if (!$reflectionType instanceof \ReflectionNamedType) {
            return null;
        }

        $type = $reflectionType->getName();
        if (!is_a($type, Collection::class, true)) {
            return null;
        }

        $relationAttributeClasses = [
            'manyToOne' => ManyToOne::class,
            'manyToMany' => ManyToMany::class,
            'oneToMany' => OneToMany::class,
            'oneToOne' => OneToOne::class,
        ];

        foreach ($relationAttributeClasses as $relationType => $attributeClass) {
            $attribute = $property->getAttributes($attributeClass);
            if (!empty($attribute)) {
                $instance = $attribute[0]->newInstance();

                return [
                    'relationType' => $relationType,
                    'targetEntity' => $instance->targetEntity,
                ];
            }
        }

        return null;
    }

    private function hasClassEntityOrTargetEntityAttributes(\ReflectionClass $reflectionClass): bool
    {
        $entity = $reflectionClass->getAttributes(Entity::class);
        $targetEntity = $reflectionClass->getAttributes(TargetEntity::class);

        return !empty($entity) || !empty($targetEntity);
    }
}
