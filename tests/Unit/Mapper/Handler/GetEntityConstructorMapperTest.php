<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Unit\Mapper\Handler;

use PBaszak\MessengerDoctrineDTOBundle\Mapper\Handler\GetEntityConstructorMapperHandler;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityConstructorMapper;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use PHPUnit\Framework\TestCase;

/** @group unit */
class GetEntityConstructorMapperTest extends TestCase
{
    /** @test */
    public function testGetEntityConstructorMapperHandler(): void
    {
        $handler = new GetEntityConstructorMapperHandler();
        $function = function () {throw new \LogicException('This should not be called'); };
        eval($handler(
            new GetEntityConstructorMapper(
                User::class,
                UserRegistrationData::class
            )
        ));

        $dto = new UserRegistrationData('test@test.com', 'password');
        $entity = new User(
            ...$function($dto, $this->createMock(\Doctrine\ORM\EntityManagerInterface::class))
        );

        $this->assertSame($dto->email, $entity->email);
        $this->assertSame($dto->passwordHash, $entity->passwordHash);
    }
}
