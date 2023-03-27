<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class StringFormat extends MappingCallback
{
    public function __construct(
        int $priority = 0,
    ) {
        parent::__construct(
            '(string) %s',
            $priority
        );
    }
}
