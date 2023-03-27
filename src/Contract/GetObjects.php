<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Contract;

use Doctrine\Common\Collections\Criteria;

class GetObjects
{
    public function __construct(
        public readonly string $instanceOf,
        public readonly ?Criteria $criteria = null,
        public readonly bool $arrayHydration = false,
    ) {
    }
}
