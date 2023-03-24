<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetProperty;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;

#[TargetEntity(Post::class)]
class NewPost
{
    public function __construct(
        #[TargetProperty('author', User::class)]
        public readonly string $authorId,
        public readonly string $content,
    ) {
    }
}
