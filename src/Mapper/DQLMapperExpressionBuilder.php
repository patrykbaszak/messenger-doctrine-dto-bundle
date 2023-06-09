<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\MappingCallback;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetEntity;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\DTO\DQLExpression;
use PBaszak\MessengerDoctrineDTOBundle\Mapper\DTO\DQLProperty;
use PBaszak\MessengerDoctrineDTOBundle\Utils\FindMatchingProperty;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;

class DQLMapperExpressionBuilder
{
    use FindMatchingProperty;
    use GetTargetEntity;

    private const PROPERTY_ACCESS_TEMPLATE = '$%s[\'%s\']';
    private const PROPERTY_SET_TEMPLATE = '$%s%s=%s;';

    /** @var DQLExpression[] */
    private static array $expressions = [];

    public function getDQLQuery(): string
    {
        $joins = [];
        $selects = [];
        foreach (self::$expressions as $expression) {
            $selects = array_merge($selects, $expression->getPropertiesExpressions());
            $join = $expression->getJoinExpression();
            if ($join) {
                $joins[] = $join;
            } else {
                $from = $expression->getFromExpression();
            }
        }

        if (!isset($from)) {
            throw new \LogicException('No `from` expression found');
        }

        if (!empty($joins)) {
            return sprintf('SELECT %s FROM %s %s', implode(', ', $selects), $from, implode(' ', $joins));
        }

        return sprintf('SELECT %s FROM %s', implode(', ', $selects), $from);
    }

    /**
     * @return string[]
     */
    public function getOutputMapper(string $inputVariableName, string $outputVariableName): array
    {
        $output = [];
        foreach (self::$expressions as $expression) {
            foreach ($expression->getPropertiesWithOutputNames() as $property) {
                $getter = sprintf(self::PROPERTY_ACCESS_TEMPLATE, $inputVariableName, $property->path);
                foreach ($this->sortCallbacks(array_map(fn (\ReflectionAttribute $attr) => $attr->newInstance(), $property->callbacks)) as $callback) {
                    $getter = sprintf($callback->callback, $getter);
                }
                $output[] = sprintf(self::PROPERTY_SET_TEMPLATE, $outputVariableName, $this->getSetterPath($property->path), $getter);
            }
        }

        return $output;
    }

    private function getSetterPath(string $propertyPath): string
    {
        $parts = explode('__', $propertyPath);
        $output = '';
        foreach ($parts as $part) {
            $output .= sprintf('[\'%s\']', $part);
        }

        return $output;
    }

    /**
     * @param object[] $callbacks
     *
     * @return MappingCallback[]
     */
    private function sortCallbacks(array $callbacks): array
    {
        usort($callbacks, function (MappingCallback $a, MappingCallback $b) {
            return $a->priority <=> $b->priority;
        });

        return $callbacks;
    }

    /**
     * @param class-string<object>      $dtoClass
     * @param class-string<object>|null $instanceOf
     * @param string|null               $joinType   LEFT|INNER|OUTER
     */
    public function buildExpressions(
        string $alias,
        string $dtoClass,
        ?string $instanceOf = null,
        ?string $parentAlias = null,
        ?string $joinType = null,
    ): void {
        /** @var class-string<object> $entityClass */
        $entityClass = $instanceOf ?? $this->getTargetEntity($dtoClass);
        $dtoReflection = new \ReflectionClass($dtoClass);

        if (!$joinType) {
            $attributes = $dtoReflection->getAttributes(TargetEntity::class);
            if (!empty($attributes)) {
                $joinType = $attributes[0]->newInstance()->joinType;
            } else {
                $joinType = 'LEFT';
            }
        }

        $entityReflection = new \ReflectionClass($entityClass);

        $properties = [];
        foreach ($dtoReflection->getProperties() as $property) {
            if (null === ($matchingProperty = $this->findMatchingProperty($dtoReflection, $entityReflection, $property))) {
                throw new \LogicException(sprintf('Property %s::$%s does not match any property in %s', $dtoClass, $property->getName(), $entityClass));
            }

            /** @var class-string<object> $targetEntity */
            if ($targetEntity = $this->getTargetEntityIfIsDeclared($property)) {
                if (!empty($propertyAttributes = $property->getAttributes(TargetEntity::class))) {
                    $propertyJoinType = $propertyAttributes[0]->newInstance()->joinType;
                } else {
                    $propertyJoinType = null;
                }
                if (!$property->getType() instanceof \ReflectionNamedType) {
                    throw new \LogicException(sprintf('Property %s::$%s has no type or is not supported union type.', $dtoClass, $property->getName()));
                }
                /** @var class-string<object> $propertyType */
                $propertyType = $property->getType()->getName();
                $this->buildExpressions($property->getName(), $propertyType, $targetEntity, $alias, $propertyJoinType);
            } elseif (null !== ($targetEntityCollection = $this->getTargetEntityAndRelationFromProperty($property))) {
                // do nothing
            } else {
                $callbacks = array_filter($property->getAttributes(), fn (\ReflectionAttribute $attr) => $attr->newInstance() instanceof MappingCallback);
                $properties[] = new DQLProperty($matchingProperty->getName(), $property->getName(), $callbacks);
            }
        }

        self::$expressions[] = new DQLExpression($properties, $alias, $entityClass, $parentAlias);
    }
}
