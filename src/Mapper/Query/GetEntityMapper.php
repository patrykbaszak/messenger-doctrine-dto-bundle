<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Query;

use PBaszak\MessengerCacheBundle\Attribute\Cache;
use PBaszak\MessengerCacheBundle\Contract\Required\Cacheable;

#[Cache(pool: 'messenger_doctrine_dto.mapper')]
class GetEntityMapper implements Cacheable
{
    /**
     * @param class-string<object> $entityClass
     * @param class-string<object> $dtoClass
     */
    public function __construct(
        public readonly string $entityClass,
        public readonly string $dtoClass,
        public readonly bool $ignoreConstuctorArguments = false,
        public readonly bool $dtoAsArray = false
    ) {
    }
}
