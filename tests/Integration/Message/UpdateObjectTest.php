<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Contract\UpdateObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserUpdateData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group integration */
class UpdateObjectTest extends KernelTestCase
{
    use HandleTrait;

    private EntityManagerInterface $_em;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
        $this->_em = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test */
    public function testUpdatePutObject(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $entity = $this->handle($message);
        $repo = $this->_em->getRepository(User::class);
        $this->assertSame($entity, $repo->find($entity->id));

        $dto = new UserUpdateData($entity->id, 'test2@example.com');
        $message = new UpdateObject($dto);
        $entity = $this->handle($message);

        $this->assertSame($dto->email, $entity->email);
        $this->assertNotEmpty($entity->passwordHash);

        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testUpdatePutObjectWithArray(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $entity = $this->handle($message);
        $repo = $this->_em->getRepository(User::class);
        $this->assertSame($entity, $repo->find($entity->id));

        $dto = new UserUpdateData($entity->id, 'test2@example.com');
        $message = new UpdateObject(
            [
                'id' => $dto->id,
                'email' => $dto->email,
            ],
            UserUpdateData::class
        );
        $entity = $this->handle($message);

        $this->assertSame($dto->email, $entity->email);
        $this->assertNotEmpty($entity->passwordHash);

        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testUpdatePutObjectWithAnonymousObject(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $entity = $this->handle($message);
        $repo = $this->_em->getRepository(User::class);
        $this->assertSame($entity, $repo->find($entity->id));

        $dto = new UserUpdateData($entity->id, 'test2@example.com');
        $message = new UpdateObject(
            (object) [
                'id' => $dto->id,
                'email' => $dto->email,
            ],
            UserUpdateData::class
        );
        $entity = $this->handle($message);

        $this->assertSame($dto->email, $entity->email);
        $this->assertNotEmpty($entity->passwordHash);

        $this->_em->getConnection()->rollBack();
    }
}
