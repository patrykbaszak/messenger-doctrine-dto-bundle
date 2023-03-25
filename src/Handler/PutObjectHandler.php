<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityConstructorMapper;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityMapper;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetIdentifier;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler()]
class PutObjectHandler
{
    use HandleTrait;
    use GetTargetEntity;
    use GetIdentifier;

    /** @var array<int|string,object> */
    private static array $persistedEntities = [];
    private static int $initialized = 0;

    public function __construct(
        private EntityManagerInterface $_em,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(PutObject $message): object
    {
        ++self::$initialized;
        /** @var class-string<object> $targetEntity */
        $targetEntity = $this->getTargetEntity($message->instanceOf ?? $message->dto);
        $dtoClass = $message->instanceOf ?? get_class($message->dto);
        if (null !== ($id = $this->getIdentifier($message->dto))) {
            $entity = self::$persistedEntities[$id] ?? $this->_em->find($targetEntity, $id);
        }

        $this->_em->getConnection()->beginTransaction();
        try {
            $entityCreated = false;
            if (!isset($entity)) {
                $function = function (object $dto): array {throw new \LogicException('This should not be called'); };
                eval($this->handle(new GetEntityConstructorMapper($targetEntity, $dtoClass, is_array($message->dto))));
                $entity = new $targetEntity(
                    ...$function($message->dto)
                );
                $entityCreated = true;
            }

            $function = function (object $entity, object $dto): void {throw new \LogicException('This should not be called'); };
            eval($this->handle(new GetEntityMapper($targetEntity, $dtoClass, $entityCreated, is_array($message->dto))));
            $function($entity, $message->dto);

            $id = $this->getIdentifier($entity);
            if (!isset(self::$persistedEntities[$id])) {
                $this->_em->persist($entity);
                self::$persistedEntities[$id] = $entity;
            }
        } catch (\Throwable $e) {
            $this->_em->getConnection()->rollBack();
            throw $e;
        }

        --self::$initialized;
        if (0 === self::$initialized) {
            $this->_em->flush();
            self::$persistedEntities = [];
        }

        return $entity;
    }
}
