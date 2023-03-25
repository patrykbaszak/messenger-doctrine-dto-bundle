<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPost;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPostWithExistingUser;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPostWithUserRegistration;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserUpdateData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Comment;
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

    /** @test */
    public function testPutObjectWhichActuallyIsEntity(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = new User('test@test.eu', 'password');
        $entity = $this->handle(new PutObject($user));
        $this->_em->getConnection()->rollBack();

        $this->assertSame($user->email, $entity->email);
        $this->assertSame($user->passwordHash, $entity->passwordHash);
    }

    /** @test */
    public function testPutObjectWhichActuallyIsEntityAndHasNestedEntity(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = new User('test@test.eu', 'password');
        $post = new Post($user, 'test');
        $comment = new Comment($user, $post, 'test');
        $entity = $this->handle(new PutObject($comment));
        $this->_em->getConnection()->rollBack();

        $this->assertSame($user->email, $entity->post->author->email);
        $this->assertSame($user->passwordHash, $entity->post->author->passwordHash);
        $this->assertSame($post->content, $entity->post->content);
        $this->assertSame($comment->content, $entity->content);
    }

    /** @test */
    public function testPutObjectWithNestedObjectWhichNotExists(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $dto = new NewPostWithUserRegistration('some interesting content', new UserRegistrationData('joe.doe@example.com', 'passwd'));
        $message = new PutObject($dto);
        $post = $this->handle($message);
        $user = $post->author;

        $this->assertSame($dto->getContent(), $post->content);
        $this->assertSame($user, $post->author);
        $this->assertSame($post, $this->_em->find(Post::class, $post->id));
        $this->assertSame($user, $this->_em->find(User::class, $user->id));

        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testPutObjectWithNestedObjectWhichExists(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = $this->handle(new PutObject(new UserRegistrationData('joe.doe@example.com', 'passwd')));
        $dto = new NewPostWithExistingUser('some interesting content', ['id' => $user->id]);
        $message = new PutObject($dto);
        $post = $this->handle($message);

        $this->assertSame($dto->getContent(), $post->content);
        $this->assertSame($user, $post->author);
        $this->assertSame($post, $this->_em->find(Post::class, $post->id));
        $this->assertSame($user, $this->_em->find(User::class, $user->id));

        $this->_em->getConnection()->rollBack();
    }
}
