<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils\Query;

use PBaszak\MessengerDoctrineDTOBundle\Utils\Mapper\EntityMapperExpressionBuilder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetEntityMapperHandler
{
    private const FUNCTION_TEMPLATE = '$function = function (%s $entity, %s $dto): void {%s};';

    public function __invoke(GetEntityMapper $query): string
    {
        $expressionBuilder = new EntityMapperExpressionBuilder(
            $query->dtoClass,
            $query->entityClass
        );

        $argumentsMapper = $expressionBuilder->buildSetterExpressions($query->ignoreConstuctorArguments);

        return sprintf(
            self::FUNCTION_TEMPLATE,
            $query->entityClass,
            $query->dtoClass,
            implode('', $argumentsMapper)
        );
    }
}
