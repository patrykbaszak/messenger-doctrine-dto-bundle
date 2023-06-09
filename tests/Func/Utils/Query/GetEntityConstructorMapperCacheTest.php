<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Tests\Unit\Utils\Query;

use PBaszak\MessengerCacheBundle\Contract\Replaceable\MessengerCacheManagerInterface;
use PBaszak\MessengerCacheBundle\Provider\CacheKeyProvider;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityConstructorMapper;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\DTO\UserRegistrationData;
use PBaszak\MessengerDoctrineDTOBundle\Tests\Helper\Application\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\HandleTrait;

/** @group func */
class GetEntityConstructorMapperCacheTest extends KernelTestCase
{
    use HandleTrait;

    private MessengerCacheManagerInterface $cacheManager;

    protected function setUp(): void
    {
        $this->messageBus = self::getContainer()->get('cachedMessage.bus');
        $this->cacheManager = self::getContainer()->get(MessengerCacheManagerInterface::class);
    }

    /** @test */
    public function shouldCacheMapperFunction(): void
    {
        $message = new GetEntityConstructorMapper(
            User::class,
            UserRegistrationData::class
        );

        eval($this->handle($message));
        $key = (new CacheKeyProvider())->createKey($message);
        $adapter = (new \ReflectionObject($this->cacheManager))->getProperty('pools')->getValue($this->cacheManager)['messenger_doctrine_dto.mapper'];

        $this->assertTrue($adapter->hasItem($key));
    }
}
