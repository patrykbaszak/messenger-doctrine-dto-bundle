<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils\Query;

use Doctrine\ORM\EntityManagerInterface;
use PBaszak\MessengerDoctrineDTOBundle\Utils\Mapper\EntityMapperExpressionBuilder;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler()]
class GetEntityConstructorMapperHandler
{
    private const FUNCTION_TEMPLATE = '$function = function (%s $dto, %s $_em): array {$arguments=[];%sreturn $arguments;};';

    public function __invoke(GetEntityConstructorMapper $query): string
    {
        $expressionBuilder = new EntityMapperExpressionBuilder(
            sourceClass: $query->dtoClass,
            targetClass: $query->entityClass,
            sourceVariableName: 'dto',
            targetArgumentsVariableName: 'arguments',
            entityManagerVariableName: '_em'
        );

        $argumentsMapper = $expressionBuilder->buildConstructorExpressions();

        return sprintf(
            self::FUNCTION_TEMPLATE,
            $query->dtoClass,
            EntityManagerInterface::class,
            implode('', $argumentsMapper)
        );
    }
}
