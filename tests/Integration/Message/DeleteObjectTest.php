<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\DeleteObject;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group integration */
class DeleteObjectTest extends KernelTestCase
{
    use HandleTrait;

    private EntityManagerInterface $_em;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
        $this->_em = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test */
    public function testDeleteObject(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $entity = $this->handle($message);
        $this->assertSame($entity, $this->_em->find(User::class, $entity->id));

        $message = new DeleteObject($entity);
        $entity = $this->handle($message);

        $this->assertNull($this->_em->find(User::class, $entity->id));

        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testDeleteObjectWithArray(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $entity = $this->handle($message);
        $this->assertSame($entity, $this->_em->find(User::class, $entity->id));

        $message = new DeleteObject(
            [
                'id' => $entity->id,
            ],
            User::class,
        );
        $entity = $this->handle($message);

        $this->assertNull($this->_em->find(User::class, $entity->id));

        $this->_em->getConnection()->rollBack();
    }
}
