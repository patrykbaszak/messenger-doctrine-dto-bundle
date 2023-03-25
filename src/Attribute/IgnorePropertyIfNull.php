<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class IgnorePropertyIfNull
{
    public function __construct(
        public readonly bool $ignore = true,
    ) {
    }
}
