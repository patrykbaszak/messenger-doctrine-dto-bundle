<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Unit\Utils\Query;

use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Query\GetEntityConstructorMapper;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Query\GetEntityConstructorMapperHandler;
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
            ...$function($dto)
        );

        $this->assertSame($dto->email, $entity->email);
        $this->assertSame($dto->passwordHash, $entity->passwordHash);
    }
}
