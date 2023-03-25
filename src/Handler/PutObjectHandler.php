<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityConstructorMapper;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityMapper;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler()]
class PutObjectHandler
{
    use HandleTrait;
    use GetTargetEntity;

    public function __construct(
        private EntityManagerInterface $_em,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(PutObject $message): object
    {
        /** @var class-string<object> $targetEntity */
        $targetEntity = $this->getTargetEntity($message->dto);
        $dtoClass = get_class($message->dto);
        $_er = $this->_em->getRepository($targetEntity);

        if (null !== $message->id) {
            $entity = $_er->find($message->id);
        }

        $this->_em->getConnection()->beginTransaction();
        try {
            $entityCreated = false;
            if (!isset($entity)) {
                $function = function (object $dto, EntityManagerInterface $_em): array {throw new \LogicException('This should not be called'); };
                eval($this->handle(new GetEntityConstructorMapper($targetEntity, $dtoClass)));
                $entity = new $targetEntity(
                    ...$function($message->dto, $this->_em)
                );
                $entityCreated = true;
            }

            $function = function (object $entity, object $dto, EntityManagerInterface $_em): void {throw new \LogicException('This should not be called'); };
            eval($this->handle(new GetEntityMapper($targetEntity, $dtoClass, $entityCreated)));
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
