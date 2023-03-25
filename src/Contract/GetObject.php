<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Contract;

class GetObject
{
    public function __construct(
        public readonly string $dto,
        public readonly int|string $id,
        public readonly bool $arrayHydration = false,
    ) {
    }
}
