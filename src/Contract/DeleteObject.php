<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Contract;

/**
 * Delete entity from database.
 */
class DeleteObject
{
    /**
     * @param array<string,mixed>|object $object     Incoming data
     * @param string|null                $instanceOf Incoming data class name (if it's an array or anonymous object)
     */
    public function __construct(
        public readonly array|object $object,
        public readonly ?string $instanceOf = null,
    ) {
    }
}
