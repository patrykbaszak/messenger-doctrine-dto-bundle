<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Like
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid")]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public User $user;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: "likes")]
    #[ORM\JoinColumn(nullable: true)]
    public ?Post $post = null;

    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: "likes")]
    #[ORM\JoinColumn(nullable: true)]
    public ?Comment $comment = null;

    #[ORM\Column(type: "datetime")]
    public \DateTimeInterface $createdAt;

    public function __construct(User $user, Post $post = null, Comment $comment = null)
    {
        if ($post === null && $comment === null) {
            throw new \InvalidArgumentException('Both `post` and `comment` cannot be null.');
        }
        if ($post && $comment) {
            throw new \InvalidArgumentException('Both `post` and `comment` cannot be set.');
        }

        $this->id = Uuid::v4()->toRfc4122();
        $this->user = $user;
        $this->post = $post;
        $this->comment = $comment;
        $this->createdAt = new \DateTimeImmutable();
    }
}
