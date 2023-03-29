<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\GetObjects;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPost;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\PostObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group integration */
class GetObjectsTest extends KernelTestCase
{
    use HandleTrait;

    private EntityManagerInterface $_em;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
        $this->_em = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    /** @test */
    public function testGetObject(): void
    {
        $this->_em->getConnection()->beginTransaction();
        foreach (range(1, 10) as $i) {
            $user = $this->handle(new PutObject(
                new UserRegistrationData(
                    'user'.$i,
                    'user'.$i.'@example.com',
                    'password'.$i,
                )
            ));

            foreach (range(1, 10) as $j) {
                $post = $this->handle(new PutObject(
                    new NewPost(
                        $user->id,
                        'Post '.$j,
                    )
                ));
            }
        }

        $objects = $this->handle(new GetObjects(Post::class, null, true));

        $this->assertCount(100, $objects);
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testGetObjectWithCriteria(): void
    {
        $this->_em->getConnection()->beginTransaction();
        foreach (range(1, 3) as $i) {
            $user = $this->handle(new PutObject(
                new UserRegistrationData(
                    'user'.$i,
                    'user'.$i.'@example.com',
                    'password'.$i,
                )
            ));

            foreach (range(1, 3) as $j) {
                $post = $this->handle(new PutObject(
                    new NewPost(
                        $user->id,
                        'Post '.$j,
                    )
                ));
            }
        }

        $objects = $this->handle(new GetObjects(
            Post::class,
            Criteria::create()->where(
                Criteria::expr()->eq('author.id', $user->id)
            )->andWhere(
                Criteria::expr()->eq('content', 'Post 3')
            ),
            true
        ));

        $this->assertCount(1, $objects);
        $this->assertEquals('Post 3', $objects[0]['content']);
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testGetObjectWithCriteriaOrderBy(): void
    {
        $this->_em->getConnection()->beginTransaction();
        foreach (range(1, 3) as $i) {
            $user = $this->handle(new PutObject(
                new UserRegistrationData(
                    'user'.$i,
                    'user'.$i.'@example.com',
                    'password'.$i,
                )
            ));

            foreach (range(1, 3) as $j) {
                $post = $this->handle(new PutObject(
                    new NewPost(
                        $user->id,
                        'Post '.$j,
                    )
                ));
            }
        }

        $objects = $this->handle(new GetObjects(
            Post::class,
            Criteria::create()->orderBy(
                ['content' => 'DESC']
            ),
            true
        ));

        $this->assertCount(9, $objects);
        $this->assertEquals('Post 3', $objects[0]['content']);
        $this->assertEquals('Post 3', $objects[1]['content']);
        $this->assertEquals('Post 3', $objects[2]['content']);
        $this->assertEquals('Post 2', $objects[3]['content']);
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testGetObjectWithCriteriaOrderByAsc(): void
    {
        $this->_em->getConnection()->beginTransaction();
        foreach (range(1, 3) as $i) {
            $user = $this->handle(new PutObject(
                new UserRegistrationData(
                    'user'.$i,
                    'user'.$i.'@example.com',
                    'password'.$i,
                )
            ));

            foreach (range(1, 3) as $j) {
                $post = $this->handle(new PutObject(
                    new NewPost(
                        $user->id,
                        'Post '.$j,
                    )
                ));
            }
        }

        $objects = $this->handle(new GetObjects(
            Post::class,
            Criteria::create()->orderBy(
                ['content' => 'ASC']
            ),
            true
        ));

        $this->assertCount(9, $objects);
        $this->assertEquals('Post 1', $objects[0]['content']);
        $this->assertEquals('Post 1', $objects[1]['content']);
        $this->assertEquals('Post 1', $objects[2]['content']);
        $this->assertEquals('Post 2', $objects[3]['content']);
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testGetObjectWithLimit(): void
    {
        $this->_em->getConnection()->beginTransaction();
        foreach (range(1, 10) as $i) {
            $user = $this->handle(new PutObject(
                new UserRegistrationData(
                    'user'.$i,
                    'user'.$i.'@example.com',
                    'password'.$i,
                )
            ));

            foreach (range(1, 10) as $j) {
                $post = $this->handle(new PutObject(
                    new NewPost(
                        $user->id,
                        'Post '.$j,
                    )
                ));
            }
        }

        $objects = $this->handle(new GetObjects(
            Post::class,
            Criteria::create()->setMaxResults(10),
            false
        ));

        $this->assertCount(10, $objects);
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testGetObjectWithLimitAndOffset(): void
    {
        $this->_em->getConnection()->beginTransaction();
        foreach (range(1, 9) as $i) {
            $user = $this->handle(new PutObject(
                new UserRegistrationData(
                    'user'.$i,
                    'user'.$i.'@example.com',
                    'password'.$i,
                )
            ));

            foreach (range(1, 9) as $j) {
                $post = $this->handle(new PutObject(
                    new NewPost(
                        $user->id,
                        'Post '.$j,
                    )
                ));
            }
        }

        $objects = $this->handle(new GetObjects(
            Post::class,
            Criteria::create()
                ->setMaxResults(10)
                ->setFirstResult(10)
                ->orderBy(['content' => 'ASC']),
            true
        ));

        $this->assertSame('Post 2', $objects[0]['content']);
        $this->assertCount(10, $objects);
        $this->_em->getConnection()->rollBack();
    }

    /** @test */
    public function testGetObjectWithMappingCallback(): void
    {
        $this->_em->getConnection()->beginTransaction();
        $user = $this->handle(new PutObject(
            new UserRegistrationData(
                'user',
                'user@example.com',
                'password',
            )
        ));

        $post = $this->handle(new PutObject(
            new NewPost(
                $user->id,
                'Post',
            )
        ));

        $objects = $this->handle(
            new GetObjects(PostObject::class, null, false)
        );

        $this->assertCount(1, $objects);
        $this->assertInstanceOf(PostObject::class, $objects[0]);
        $this->assertEquals((new \DateTime())->format('Y-m-d'), $objects[0]->createdAt);
        $this->_em->getConnection()->rollBack();
    }
}
