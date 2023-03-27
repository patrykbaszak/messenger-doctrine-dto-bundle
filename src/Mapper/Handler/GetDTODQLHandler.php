<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Handler;

use PBaszak\MessengerDoctrineDTOBundle\Mapper\DQLMapperExpressionBuilder;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetDTODQL;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetDTODQLHandler
{
    public function __invoke(GetDTODQL $message): string
    {
        $dqlBuilder = new DQLMapperExpressionBuilder();
        $dqlBuilder->buildExpressions(
            'root',
            $message->dtoClass,
        );

        $dql = $dqlBuilder->getDQLQuery();

        return $dql;
    }
}
