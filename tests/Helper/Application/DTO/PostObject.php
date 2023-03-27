<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\DateTimeFormat;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\StringFormat;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;

#[TargetEntity(Post::class)]
class PostObject
{
    public function __construct(
        #[StringFormat()]
        public readonly string $id,
        public readonly string $content,
        #[DateTimeFormat('Y-m-d')]
        public readonly string $createdAt,
    ) {
    }
}
