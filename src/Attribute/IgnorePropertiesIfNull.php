<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

use Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class IgnorePropertiesIfNull
{
    public function __construct(
        public readonly bool $ignore = true,
    ) {
    }
}
