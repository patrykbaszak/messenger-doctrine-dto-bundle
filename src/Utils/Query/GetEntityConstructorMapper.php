<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache(pool: 'messenger_doctrine_dto.mapper')]
class GetEntityConstructorMapper implements Cacheable
{
    public function __construct(
        public readonly string $entityClass,
        public readonly string $dtoClass,
    ) {
    }
}
