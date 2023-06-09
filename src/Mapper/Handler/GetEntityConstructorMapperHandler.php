<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Handler;

use PBaszak\MessengerDoctrineDTOBundle\Mapper\EntityMapperExpressionBuilder;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityConstructorMapper;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetEntityConstructorMapperHandler
{
    private const FUNCTION_TEMPLATE = 'return function (%s $dto): array {$arguments=[];%sreturn $arguments;};';

    public function __invoke(GetEntityConstructorMapper $query): string
    {
        $expressionBuilder = new EntityMapperExpressionBuilder(
            sourceClass: $query->dtoClass,
            targetClass: $query->entityClass,
            sourceVariableName: 'dto',
            targetArgumentsVariableName: 'arguments',
            entityManagerVariableName: '_em',
            sourceAsArray: $query->dtoAsArray
        );

        $argumentsMapper = $expressionBuilder->buildConstructorExpressions();

        return sprintf(
            self::FUNCTION_TEMPLATE,
            $query->dtoAsArray ? 'array' : 'object',
            implode('', $argumentsMapper)
        );
    }
}
