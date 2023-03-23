<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

use Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class TargetProperty
{
    public function __construct(
        public readonly string $name
    ) {
    }
}
