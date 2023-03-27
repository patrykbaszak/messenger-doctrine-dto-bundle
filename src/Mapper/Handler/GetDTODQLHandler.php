<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Handler;

use PBaszak\MessengerDoctrineDTOBundle\Mapper\DQLMapperExpressionBuilder;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetDTODQL;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetDTODQLHandler
{
    public const FUNCTION_TEMPLATE = 'return function (array $object) {$output=[];%sreturn $output;};';

    /** @return string[] */
    public function __invoke(GetDTODQL $message): array
    {
        $dqlBuilder = new DQLMapperExpressionBuilder();
        $dqlBuilder->buildExpressions(
            'root',
            $message->dtoClass,
        );

        $dql = $dqlBuilder->getDQLQuery();
        $mapper = sprintf(
            self::FUNCTION_TEMPLATE,
            implode('', $dqlBuilder->getOutputMapper('object', 'output')),
        );

        return [$dql, $mapper];
    }
}
