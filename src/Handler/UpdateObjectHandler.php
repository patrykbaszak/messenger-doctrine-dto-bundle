<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use PBaszak\MessengerDoctrineDTOBundle\Contract\UpdateObject;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityMapper;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetIdentifier;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler()]
class UpdateObjectHandler
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

    public function __invoke(UpdateObject $message): object
    {
        /** @var class-string<object> $targetEntity */
        $targetEntity = $this->getTargetEntity($message->instanceOf ?? $message->object);
        $objectClass = $message->instanceOf ?? get_class($message->object);
        $id = $this->getIdentifier($message->object);

        if (null === $id || null === ($entity = $this->_em->find($targetEntity, $id))) {
            throw new EntityNotFoundException(sprintf('Entity %s with id %s not found.', $targetEntity, $id));
        }

        $this->_em->getConnection()->beginTransaction();
        try {
            $function = function (object $entity, object $object): void {throw new \LogicException('This should not be called'); };
            eval($this->handle(new GetEntityMapper($targetEntity, $objectClass, false, is_array($message->object))));
            $function($entity, $message->object);

            $this->_em->persist($entity);
            $this->_em->flush();
        } catch (\Throwable $e) {
            $this->_em->getConnection()->rollBack();
            throw $e;
        }

        return $entity;
    }
}
