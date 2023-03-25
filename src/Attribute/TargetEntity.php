<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

/**
 * The TargetEntity attributes always indicate that the data they point
 * to will be modified, created, or retrieved, depending on the context
 * in which they were used. If you point to this attribute on a DTO
 * property during a PutObject action, the library will attempt to fetch
 * and modify or create the specified object.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
class TargetEntity
{
    public function __construct(
        public readonly string $entityClass
    ) {
    }
}
