<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPost;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserUpdateData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group integration */
class PutObjectTest extends KernelTestCase
{
    use HandleTrait;

    private EntityManagerInterface $_em;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
        $this->_em = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test */
    public function testPutObject(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);

        $entity = $this->handle($message);

        $this->assertInstanceOf(User::class, $entity);
        $this->assertSame($dto->email, $entity->email);

        $repo = $this->_em->getRepository(User::class);
        $this->assertSame($entity, $repo->find($entity->id));
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testPutObjectWithArray(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject(
            [
                'email' => $dto->email,
                'passwordHash' => $dto->passwordHash,
            ],
            UserRegistrationData::class
        );

        $entity = $this->handle($message);

        $this->assertInstanceOf(User::class, $entity);
        $this->assertSame($dto->email, $entity->email);

        $repo = $this->_em->getRepository(User::class);
        $this->assertSame($entity, $repo->find($entity->id));
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testPutObjectWithAnonymousObject(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject(
            (object) [
                'email' => $dto->email,
                'passwordHash' => $dto->passwordHash,
            ],
            UserRegistrationData::class
        );

        $entity = $this->handle($message);

        $this->assertInstanceOf(User::class, $entity);
        $this->assertSame($dto->email, $entity->email);

        $repo = $this->_em->getRepository(User::class);
        $this->assertSame($entity, $repo->find($entity->id));
        $this->_em->getConnection()->rollBack();
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
        $message = new PutObject($dto);
        $entity = $this->handle($message);

        $this->assertSame($dto->email, $entity->email);
        $this->assertNotEmpty($entity->passwordHash);

        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testPutObjectWithRelation(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $user = $this->handle($message);

        $dto = new NewPost($user->id, 'test');
        $message = new PutObject($dto);
        $post = $this->handle($message);

        $this->assertSame($dto->content, $post->content);
        $this->assertSame($user, $post->author);
        $repo = $this->_em->getRepository(Post::class);
        $this->assertSame($post, $repo->find($post->id));

        $this->_em->getConnection()->rollBack();
    }
}
