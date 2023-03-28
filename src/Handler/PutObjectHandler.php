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
        $targetEntity = $this->getTargetEntity($message->instanceOf ?? $message->object);
        $objectClass = $message->instanceOf ?? get_class($message->object);
        if (null !== ($id = $this->getIdentifier($message->object))) {
            $entity = self::$persistedEntities[$id] ?? $this->_em->find($targetEntity, $id);
        }
        if (!isset($entity) && $message->object instanceof $targetEntity) {
            $entity = $message->object;
        }

        if (1 === self::$initialized) {
            $this->_em->beginTransaction();
        }
        try {
            $entityCreated = false;
            if (!isset($entity)) {
                $function = eval($this->handle(new GetEntityConstructorMapper($targetEntity, $objectClass, is_array($message->object))));
                $entity = new $targetEntity(
                    ...$function($message->object)
                );
                $entityCreated = true;
            }

            $function = eval($this->handle(new GetEntityMapper($targetEntity, $objectClass, $entityCreated, is_array($message->object))));
            $function($entity, $message->object);

            $id = $this->getIdentifier($entity);
            if (!isset(self::$persistedEntities[$id])) {
                $this->_em->persist($entity);
                self::$persistedEntities[$id] = $entity;
            }
        } catch (\Throwable $e) {
            $this->_em->rollBack();
            throw $e;
        }

        --self::$initialized;
        if (0 === self::$initialized) {
            $this->_em->flush();
            $this->_em->commit();
            self::$persistedEntities = [];
        }

        return $entity;
    }
}
