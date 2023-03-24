<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils\Query;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Mapper\EntityMapperExpressionBuilder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetEntityMapperHandler
{
    private const FUNCTION_TEMPLATE = '$function = function (%s $entity, %s $dto, %s $_em): void {%s};';

    public function __invoke(GetEntityMapper $query): string
    {
        $expressionBuilder = new EntityMapperExpressionBuilder(
            sourceClass: $query->dtoClass,
            targetClass: $query->entityClass,
            sourceVariableName: 'dto',
            targetVariableName: 'entity',
            entityManagerVariableName: '_em'
        );

        $argumentsMapper = $expressionBuilder->buildSetterExpressions($query->ignoreConstuctorArguments);

        return sprintf(
            self::FUNCTION_TEMPLATE,
            $query->entityClass,
            $query->dtoClass,
            EntityManagerInterface::class,
            implode('', $argumentsMapper)
        );
    }
}
