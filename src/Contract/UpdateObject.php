<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Contract;

use Doctrine\ORM\EntityNotFoundException;

/**
 * Update entity in database.
 *
 * @throws EntityNotFoundException if entity with given id does not exist
 */
class UpdateObject
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
