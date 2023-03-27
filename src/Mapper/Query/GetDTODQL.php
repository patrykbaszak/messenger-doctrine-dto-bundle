<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache(pool: 'messenger_doctrine_dto.mapper')]
class GetDTODQL implements Cacheable
{
    public function __construct(
        /** @var class-string<object> $dtoClass */
        public readonly string $dtoClass,
    ) {
    }
}
