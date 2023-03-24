<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Message\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Query\GetEntityConstructorMapper;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Query\GetEntityMapper;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler()]
class PutObjectHandler
{
    use HandleTrait;

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

            $function = function (object $dto, object $entity, EntityManagerInterface $_em): void {throw new \LogicException('This should not be called'); };
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

    private function getTargetEntity(object $dto): string
    {
        $attributes = (new \ReflectionClass($dto))->getAttributes(TargetEntity::class);

        if (0 === count($attributes)) {
            throw new \LogicException(sprintf('No %s attribute found on %s', TargetEntity::class, get_class($dto)));
        }

        return $attributes[0]->newInstance()->entityClass;
    }
}
