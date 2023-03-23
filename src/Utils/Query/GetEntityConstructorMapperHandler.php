<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils\Query;

use PBaszak\MessengerDoctrineDTOBundle\Utils\Mapper\EntityMapperExpressionBuilder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetEntityConstructorMapperHandler
{
    private const FUNCTION_TEMPLATE = '$function = function (%s $dto): array {$arguments=[];%sreturn $arguments;};';

    public function __invoke(GetEntityConstructorMapper $query): string
    {
        $expressionBuilder = new EntityMapperExpressionBuilder(
            $query->dtoClass,
            $query->entityClass
        );

        $argumentsMapper = $expressionBuilder->buildConstructorExpressions();

        return sprintf(
            self::FUNCTION_TEMPLATE,
            $query->dtoClass,
            implode('', $argumentsMapper)
        );
    }
}
