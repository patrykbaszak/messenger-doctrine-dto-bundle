<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use PBaszak\MessengerDoctrineDTOBundle\Contract\UpdateObject;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityMapper;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler()]
class UpdateObjectHandler
{
    use HandleTrait;
    use GetTargetEntity;

    public function __construct(
        private EntityManagerInterface $_em,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(UpdateObject $message): object
    {
        /** @var class-string<object> $targetEntity */
        $targetEntity = $this->getTargetEntity($message->dto);
        $dtoClass = get_class($message->dto);
        $_er = $this->_em->getRepository($targetEntity);
        $entity = $_er->find($message->id);

        if (null === $entity) {
            throw new EntityNotFoundException(sprintf('Entity %s with id %s not found.', $targetEntity, $message->id));
        }

        $this->_em->getConnection()->beginTransaction();
        try {
            $function = function (object $entity, object $dto, EntityManagerInterface $_em): void {throw new \LogicException('This should not be called'); };
            eval($this->handle(new GetEntityMapper($targetEntity, $dtoClass, false)));
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
