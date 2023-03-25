<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Contract;

/**
 * Create or update entity in database.
 */
class PutObject
{
    /**
     * @param array<string,mixed>|object $dto        Incoming data
     * @param string|null                $instanceOf Incoming data class name (if it's an array or anonymous object)
     */
    public function __construct(
        public readonly array|object $dto,
        public readonly ?string $instanceOf = null,
    ) {
    }
}
