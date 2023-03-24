<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TargetProperty
{
    /**
     * @param string                    $name   - name of the property/parameter in the Entity
     * @param class-string<object>|null $entity - name of the Entity class
     *
     * If $entity is not null then mapper will try to find entity by id which should be in the property.
     * If $entity will not be found then mapper will try asign `null` to the target property/parameter.
     */
    public function __construct(
        public readonly string $name,
        public readonly ?string $entity = null,
    ) {
    }
}
