<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetProperty;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;

#[TargetEntity(Post::class)]
class NewPostWithUserRegistration
{
    public function __construct(
        private string $content,
        #[TargetProperty('author')]
        private UserRegistrationData $user
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUser(): UserRegistrationData
    {
        return $this->user;
    }
}
