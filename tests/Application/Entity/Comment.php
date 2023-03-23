<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class Comment
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid")]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public User $author;

    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(nullable: false)]
    public Post $post;

    #[ORM\Column(type: "text")]
    public string $content;

    #[ORM\Column(type: "datetime")]
    public \DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    public ?\DateTimeInterface $modifiedAt = null;

    public function __construct(User $author, Post $post, string $content)
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->author = $author;
        $this->post = $post;
        $this->content = $content;
        $this->createdAt = new \DateTimeImmutable();
    }
}
