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
        $targetEntity = $this->getTargetEntity($message->instanceOf ?? $message->dto);
        $dtoClass = $message->instanceOf ?? get_class($message->dto);
        $id = $this->getIdentifier($message->dto);

        if (null === $id || null === ($entity = $this->_em->find($targetEntity, $id))) {
            throw new EntityNotFoundException(sprintf('Entity %s with id %s not found.', $targetEntity, $id));
        }

        $this->_em->getConnection()->beginTransaction();
        try {
            if ($targetEntity === $dtoClass) {
                $this->_em->persist($entity);
                $this->_em->flush();

                return $entity;
            }

            $function = function (object $entity, object $dto): void {throw new \LogicException('This should not be called'); };
            eval($this->handle(new GetEntityMapper($targetEntity, $dtoClass, false, is_array($message->dto))));
            $function($entity, $message->dto);

            $this->_em->persist($entity);
            $this->_em->flush();
        } catch (\Throwable $e) {
            $this->_em->getConnection()->rollBack();
            throw $e;
        }

        return $entity;
    }
}
