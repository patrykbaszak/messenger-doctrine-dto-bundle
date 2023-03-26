<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Contract\UpdateObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPost;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPostWithUserRegistration;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserUpdateData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Comment;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;
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

    /** @test */
    public function testUpdateObjectWithRelation(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new UserRegistrationData('test@test.eu', 'password');
        $message = new PutObject($dto);
        $user = $this->handle($message);

        $dto = new NewPost($user->id, 'test');
        $message = new PutObject($dto);
        $post = $this->handle($message);

        $dto = new NewPostWithUserRegistration('test2', new UserRegistrationData('test@example.com', 'passwd'));
        $message = new UpdateObject(
            [
                'id' => $post->id,
                'content' => $dto->getContent(),
                'user' => $dto->getUser(),
            ],
            NewPostWithUserRegistration::class,
        );
        $post = $this->handle($message);

        $this->assertSame($dto->getContent(), $post->content);
        $this->assertSame($dto->getUser()->email, $post->author->email);
        $this->assertSame($post, $this->_em->find(Post::class, $post->id));

        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testUpdateObjectWhichActuallyIsEntity(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = new User('test@test.eu', 'password');
        $entity = $this->handle(new PutObject($user));
        $user->email = 'test@example.com';
        $entity = $this->handle(new UpdateObject($user));

        $this->_em->getConnection()->rollBack();

        $this->assertSame($user->email, $entity->email);
        $this->assertSame($user->passwordHash, $entity->passwordHash);
    }

    /** @test */
    public function testUpdateObjectWhichActuallyIsEntityWithArray(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = new User('test@test.eu', 'password');
        $entity = $this->handle(new PutObject($user));
        $user->email = 'test@example.com';
        $entity = $this->handle(new UpdateObject(
            [
                'id' => $user->id,
                'email' => $user->email,
            ],
            User::class
        ));

        $this->_em->getConnection()->rollBack();

        $this->assertSame($user->email, $entity->email);
        $this->assertSame($user->passwordHash, $entity->passwordHash);
    }

    /** @test */
    public function testUpdateObjectWhichActuallyIsEntityAndHasNestedEntity(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = new User('test@test.eu', 'password');
        $post = new Post($user, 'test');
        $comment = new Comment($user, $post, 'test');
        $entity = $this->handle(new PutObject($comment));

        $user->email = 'joe.doe@test.pl';
        $post->content = 'test2';
        $comment->content = 'test3';

        $entity = $this->handle(new UpdateObject($comment));

        $this->_em->getConnection()->rollBack();

        $this->assertSame($user->email, $entity->post->author->email);
        $this->assertSame($post->content, $entity->post->content);
        $this->assertSame($comment->content, $entity->content);
    }
}
