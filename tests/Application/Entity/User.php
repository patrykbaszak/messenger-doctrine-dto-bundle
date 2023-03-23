<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Application\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: "uuid")]
    public string $id;

    #[ORM\Column(type: "string", unique: true)]
    public string $email;

    #[ORM\Column(type: "string")]
    public string $passwordHash;

    #[ORM\Column(type: "boolean")]
    public bool $isEmailVerified = false;

    #[ORM\Column(type: "json")]
    public array $roles = [];

    #[ORM\Column(type: "datetime")]
    public \DateTimeInterface $createdAt;

    #[ORM\Column(type: "datetime", nullable: true)]
    public ?\DateTimeInterface $modifiedAt = null;

    public function __construct(
        string $email,
        string $passwordHash,
        array $roles = []
    ) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->createdAt = new \DateTimeImmutable();
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
    }
}
