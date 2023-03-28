<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\DateTimeFormat;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\StringFormat;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'posts')]
class Post
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[StringFormat()]
    public string $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    public User $author;

    #[ORM\Column(type: 'text')]
    public string $content;

    #[ORM\OneToMany(targetEntity: Like::class, mappedBy: 'post')]
    public Collection $likes;

    #[ORM\Column(type: 'datetime')]
    #[DateTimeFormat(\DateTime::ISO8601_EXPANDED)]
    public \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[DateTimeFormat(\DateTime::ISO8601_EXPANDED)]
    public ?\DateTimeInterface $modifiedAt = null;

    public function __construct(User $author, string $content)
    {
        $this->id = Uuid::v4()->toRfc4122();
        $this->author = $author;
        $this->content = $content;
        $this->likes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }
}
