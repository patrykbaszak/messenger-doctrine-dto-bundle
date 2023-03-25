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

    public function __construct(
        private EntityManagerInterface $_em,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(PutObject $message): object
    {
        if ($message->instanceOf && $message->dto instanceof $message->instanceOf) {
            throw new \LogicException('If You use instanceOf it means that dto is an array or anonymous object. So You cannot use it as an object. Clear the instanceOf property or use the dto as an object.');
        }

        /** @var class-string<object> $targetEntity */
        $targetEntity = $this->getTargetEntity($message->instanceOf ?? $message->dto);
        $dtoClass = $message->instanceOf ?? get_class($message->dto);
        if (null !== ($id = $this->getIdentifier($message->dto))) {
            $entity = $this->_em->find($targetEntity, $id);
        }

        $this->_em->getConnection()->beginTransaction();
        try {
            $entityCreated = false;
            if (!isset($entity)) {
                $function = function (object $dto, EntityManagerInterface $_em): array {throw new \LogicException('This should not be called'); };
                eval($this->handle(new GetEntityConstructorMapper($targetEntity, $dtoClass, is_array($message->dto))));
                $entity = new $targetEntity(
                    ...$function($message->dto, $this->_em)
                );
                $entityCreated = true;
            }

            $function = function (object $entity, object $dto, EntityManagerInterface $_em): void {throw new \LogicException('This should not be called'); };
            eval($this->handle(new GetEntityMapper($targetEntity, $dtoClass, $entityCreated, is_array($message->dto))));
            $function($entity, $message->dto, $this->_em);

            $this->_em->persist($entity);
            $this->_em->flush();
        } catch (\Throwable $e) {
            $this->_em->getConnection()->rollBack();
            throw $e;
        }

        return $entity;
    }
}
