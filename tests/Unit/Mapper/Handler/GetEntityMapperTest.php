<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Unit\Mapper\Handler;

use PBaszak\MessengerDoctrineDTOBundle\Mapper\Handler\GetEntityMapperHandler;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityMapper;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use PHPUnit\Framework\TestCase;

/** @group unit */
class GetEntityMapperTest extends TestCase
{
    /** @test */
    public function testGetEntityMapperHandler(): void
    {
        $handler = new GetEntityMapperHandler();
        $function = function () {throw new \LogicException('This should not be called'); };
        eval($handler(
            new GetEntityMapper(
                User::class,
                UserRegistrationData::class
            )
        ));

        $dto = new UserRegistrationData('test@test.com', 'password');
        $entity = new User('test@example.com', 'Pa$$word');

        $function($entity, $dto, $this->createMock(\Doctrine\ORM\EntityManagerInterface::class));

        $this->assertSame($dto->email, $entity->email);
        $this->assertSame($dto->passwordHash, $entity->passwordHash);
    }
}
