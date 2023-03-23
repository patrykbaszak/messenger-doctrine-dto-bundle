<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\IgnorePropertiesIfNull;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;

#[IgnorePropertiesIfNull()]
#[TargetEntity(User::class)]
class UserUpdateData
{
    public function __construct(
        public readonly ?string $email = null,
        public readonly ?string $passwordHash = null,
    ) {
    }
}
