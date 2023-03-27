<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Integration\Message;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\GetObjects;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\NewPost;
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
        $this->messageBus = self::getContainer()->get('messenger.bus.default');
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
}
