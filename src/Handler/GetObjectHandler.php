<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use PBaszak\MessengerDoctrineDTOBundle\Contract\GetObject;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Query\GetQuery;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\HandleTrait;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

#[AsMessageHandler()]
class GetObjectHandler
{
    use HandleTrait;

    public function __construct(
        private EntityManagerInterface $_em,
        private DenormalizerInterface $denormalizer,
        MessageBusInterface $messageBus,
    ) {
        $this->messageBus = $messageBus;
    }

    public function __invoke(GetObject $message) // : array|object
    {
        // $qb = $this->_em->createQueryBuilder();
        // $function = function () {throw new \LogicException('This should never be called.'); };
        // eval($this->handle(new GetQuery($message->dto)));
        // $function($qb, $message->id, $message->criteria);

        // if ($message->arrayHydration) {
        //     return $qb->getQuery()->execute(['id' => $message->id], Query::HYDRATE_ARRAY);
        // }

        // return $this->denormalizer->denormalize(
        //     $qb->getQuery()->execute(['id' => $message->id], Query::HYDRATE_ARRAY),
        //     $message->dto,
        // );
    }
}
