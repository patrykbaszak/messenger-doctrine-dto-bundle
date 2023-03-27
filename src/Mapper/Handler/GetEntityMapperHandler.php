<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\Handler;

use PBaszak\MessengerDoctrineDTOBundle\Mapper\EntityMapperExpressionBuilder;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\Query\GetEntityMapper;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetEntityMapperHandler
{
    private const FUNCTION_TEMPLATE = 'return function (%s $entity, %s $dto): void {%s};';

    public function __invoke(GetEntityMapper $query): string
    {
        $expressionBuilder = new EntityMapperExpressionBuilder(
            sourceClass: $query->dtoClass,
            targetClass: $query->entityClass,
            sourceVariableName: 'dto',
            targetVariableName: 'entity',
            entityManagerVariableName: '_em',
            sourceAsArray: $query->dtoAsArray
        );

        $argumentsMapper = $expressionBuilder->buildSetterExpressions($query->ignoreConstuctorArguments);

        return sprintf(
            self::FUNCTION_TEMPLATE,
            $query->entityClass,
            $query->dtoAsArray ? 'array' : 'object',
            implode('', $argumentsMapper)
        );
    }
}
