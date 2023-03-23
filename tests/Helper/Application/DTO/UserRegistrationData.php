<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;

#[TargetEntity(User::class)]
class UserRegistrationData
{
    public function __construct(
        public readonly string $email,
        public readonly string $passwordHash,
    ) {
    }
}
