<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Contract;

/**
 * Create or update entity in database.
 */
class PutObject
{
    public function __construct(
        public readonly object $dto,
        public readonly null|int|string $id = null,
    ) {
    }
}
