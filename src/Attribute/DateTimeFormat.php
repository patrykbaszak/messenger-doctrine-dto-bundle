<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class DateTimeFormat extends MappingCallback
{
    public function __construct(
        string $format,
        int $priority = 0,
    ) {
        parent::__construct(
            sprintf('%s?->format("%s")', '%s', $format),
            $priority
        );
    }
}
