<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetProperty;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;

#[TargetEntity(Post::class)]
class NewPostWithExistingUser
{
    public function __construct(
        private string $content,
        #[TargetProperty('author')]
        #[TargetEntity(User::class)]
        private array $user
    ) {
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getUser(): array
    {
        return $this->user;
    }
}
