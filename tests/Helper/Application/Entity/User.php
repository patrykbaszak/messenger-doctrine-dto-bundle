<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\DateTimeFormat;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\StringFormat;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    #[StringFormat()]
    public string $id;

    #[ORM\Column(type: 'string', unique: true)]
    public string $email;

    #[ORM\Column(type: 'string')]
    public string $passwordHash;

    #[ORM\Column(type: 'boolean')]
    public bool $isEmailVerified = false;

    #[ORM\Column(type: 'json')]
    public array $roles = [];

    #[ORM\Column(type: 'datetime')]
    #[DateTimeFormat(\DateTime::ISO8601_EXPANDED)]
    public \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[DateTimeFormat(\DateTime::ISO8601_EXPANDED)]
    public ?\DateTimeInterface $modifiedAt = null;

    public function __construct(
        string $email,
        string $passwordHash,
        array $roles = ['ROLE_USER']
    ) {
        $this->id = Uuid::v4()->toRfc4122();
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->roles = $roles;
        $this->createdAt = new \DateTimeImmutable();
    }
}
