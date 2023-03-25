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
    public function __construct(
        public readonly object $dto,
        public readonly int|string $id,
    ) {
    }
}
