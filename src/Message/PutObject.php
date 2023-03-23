<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Message;

class PutObject
{
    public function __construct(
        public readonly object $dto,
        public readonly null|int|string $id = null,
    ) {
    }
}
