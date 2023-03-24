<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils\Mapper;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\IgnorePropertiesIfNull;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\IgnorePropertyIfNull;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetProperty;

class EntityMapperExpressionBuilder
{
    /** Property access templates */
    private const ARRAY_PROPERTY_TEMPLATE = '%s[\'%s\']';
    private const PUBLIC_PROPERTY_TEMPLATE = '$%s%s%s';
    private const PRIVATE_PROPERTY_TEMPLATE = '$%s%s%s()';
    private const GET_ENTITY_TEMPLATE = '$%s->find(%s::class, %s)';

    /** Access methods prefixes */
    private const GETTERS = ['', 'get'];
    private const SETTERS = ['', 'set'];

    /** Expressions templates */
    private const ARRAY_ARGUMENT_TEMPLATE = '$%s[]=%s;';
    private const ARGUMENT_TEMPLATE = '%s=%s;';
    private const SETUP_TEMPLATE = '%s%s%s(%s);';

    private \ReflectionClass $targetReflection;
    private \ReflectionClass $sourceReflection;

    /**
     * @param class-string<object> $sourceClass
     * @param class-string<object> $targetClass
     */
    public function __construct(
        private string $sourceClass,
        private string $targetClass,
        private string $sourceVariableName = 'dto',
        private string $targetVariableName = 'entity',
        private string $sourceArgumentsVariableName = 'variables',
        private string $targetArgumentsVariableName = 'arguments',
        private string $entityManagerVariableName = 'entityManager',
    ) {
        $this->targetReflection = new \ReflectionClass($targetClass);
        $this->sourceReflection = new \ReflectionClass($sourceClass);

        4 === count(
            array_unique(
                [
                    $this->sourceVariableName,
                    $this->targetVariableName,
                    $this->sourceArgumentsVariableName,
                    $this->targetArgumentsVariableName,
                ]
            )
        ) ?: throw new \LogicException('Variable names must be unique.');
    }

    /**
     * @return array<string,string>
     */
    public function buildConstructorExpressions(): array
    {
        $argumentsMapper = [];
        foreach ($this->getConstructorArguments() as $parameter) {
            $argumentExpressions = $this->getArgumentExtractionCallback(
                $this->sourceReflection,
                $this->targetReflection,
                $parameter,
                'target'
            );

            $argumentsMapper[$argumentExpressions->nameInTarget] = $argumentExpressions->constructorExpression;
        }

        return array_filter($argumentsMapper);
    }

    /**
     * @return array<string,string>
     */
    public function buildSetterExpressions(bool $ignoreConstructorArguments = false): array
    {
        $argumentsMapper = [];
        foreach ($this->sourceReflection->getProperties() as $property) {
            $argumentExpressions = $this->getArgumentExtractionCallback(
                $this->sourceReflection,
                $this->targetReflection,
                $property,
                'source'
            );

            if ($ignoreConstructorArguments && in_array($argumentExpressions->nameInTarget, $this->getConstructorArgumentsList())) {
                continue;
            }

            $argumentsMapper[$argumentExpressions->nameInTarget] = $argumentExpressions->setterExpression;
        }

        return array_filter($argumentsMapper);
    }

    /** @return string[] */
    private function getConstructorArgumentsList(): array
    {
        return array_map(
            fn (\ReflectionParameter $param) => $param->getName(),
            $this->getConstructorArguments()
        );
    }

    /** @return \ReflectionParameter[] */
    private function getConstructorArguments(): array
    {
        return ($constructor = (new \ReflectionClass($this->targetClass))->getConstructor())
            ? $constructor->getParameters()
            : [];
    }

    /** @param string $from source|target */
    private function getArgumentExtractionCallback(\ReflectionClass $source, \ReflectionClass $target, string|\ReflectionProperty|\ReflectionParameter $argument, string $from = 'source'): ArgumentExpressions
    {
        if ('source' !== $from && 'target' !== $from) {
            throw new \LogicException(sprintf('Invalid argument $from value %s. Only source|target is valid.', $from));
        }

        $sourceVariableName = $source->name === $this->sourceClass ? $this->sourceVariableName : $this->targetVariableName;
        $targetVariableName = $target->name === $this->sourceClass ? $this->sourceVariableName : $this->targetVariableName;

        if ('target' === $from) {
            $targetArgument = $argument;
            $sourceArgument = $this->findMatchingProperty($target, $source, $argument);
        } else {
            $sourceArgument = $argument;
            $targetArgument = $this->findMatchingProperty($source, $target, $argument);
        }

        if (!$sourceArgument && $targetArgument instanceof \ReflectionParameter) {
            // if happen it means that we have costructor optional argument and we don't have it in source.
            return new ArgumentExpressions(
                null,
                $targetArgument->getName(),
                '',
                '',
            );
        }

        /**
         * @var string|\ReflectionProperty                      $sourceArgument
         * @var string|\ReflectionParameter|\ReflectionProperty $targetArgument
         */
        $getterExpression = ($targetProperty = $this->getTargetPropertyAttrIfEntityIsDeclared($sourceArgument))
            ? $this->getGetExistingEntityExpression($sourceVariableName, $sourceArgument, $targetProperty)
            : $this->getGetterExpression($sourceVariableName, $sourceArgument);
        $setterExpression = $this->getSetterExpression($getterExpression, $targetVariableName, $targetArgument);
        $constructorExpression = $this->getSetterExpression($getterExpression, $this->targetArgumentsVariableName, $targetArgument);

        $ignoreProperty = false;
        if ($sourceArgument instanceof \ReflectionProperty) {
            if (!empty($ignorePropertiesIfNull = $sourceArgument->getDeclaringClass()->getAttributes(IgnorePropertiesIfNull::class))) {
                $ignoreProperty = $ignorePropertiesIfNull[0]->newInstance()->ignore;
            }
            if (!empty($ignorePropertyIfNull = $sourceArgument->getAttributes(IgnorePropertyIfNull::class))) {
                $ignoreProperty = $ignorePropertyIfNull[0]->newInstance()->ignore;
            }
        }

        if ($ignoreProperty) {
            $setterExpression = sprintf('if (null !== %s) { %s }', $getterExpression, $setterExpression);
            $constructorExpression = sprintf('if (null !== %s) { %s }', $getterExpression, $constructorExpression);
        }

        return new ArgumentExpressions(
            is_string($sourceArgument) ? $sourceArgument : $sourceArgument->getName(),
            is_string($targetArgument) ? $targetArgument : $targetArgument->getName(),
            $setterExpression,
            $constructorExpression,
        );
    }

    private function findMatchingProperty(\ReflectionClass $propertySource, \ReflectionClass $propertyTarget, string|\ReflectionProperty|\ReflectionParameter $property): null|\ReflectionProperty // + maybe string|\ReflectionParameter
    {
        /* Check is property exists in source class */
        if (!in_array($property, $propertySource->getProperties()) && !in_array($property, ($constructor = $propertySource->getConstructor()) ? $constructor->getParameters() : [])) {
            throw new \LogicException(sprintf('Property %s not found in class %s', is_string($property) ? $property : $property->getName(), $propertySource->getName()));
        }

        /* If given property is just a string we can only search for same name in target class */
        if (is_string($property)) {
            return $propertyTarget->getProperty($property) ?: throw new \LogicException(sprintf('Property %s not found in class %s.', $property, $propertyTarget->getName()));
        }

        /* If TargetProperty attribute is declared in incoming property */
        if (!empty($targetPropertyAttr = $property->getAttributes(TargetProperty::class))) {
            $targetPropertyName = $targetPropertyAttr[0]->newInstance()->name;
            $targetProperty = $propertyTarget->getProperty($targetPropertyName);
            if (!$targetProperty) {
                throw new \LogicException(sprintf('Property %s not found in class %s.', $targetPropertyName, $propertyTarget->getName()));
            }

            return $targetProperty;
        }

        /* Looking for matching property in target */
        foreach ($propertyTarget->getProperties() as $targetProperty) {
            if ($targetProperty->getName() === $property->getName()) {
                return $targetProperty;
            }

            if (!empty($targetPropertyAttr = $targetProperty->getAttributes(TargetProperty::class))) {
                $targetPropertyName = $targetPropertyAttr[0]->newInstance()->name;
                if ($targetPropertyName === $property->getName()) {
                    return $targetProperty;
                }
            }
        }

        /* If searched property is optional constructor parameter so we don't need looking for matching property and can accept this situation */
        if ($property instanceof \ReflectionParameter && $property->isOptional()) {
            return null;
        }

        throw new \LogicException(sprintf('Property %s not found in class %s.', $property->getName(), $propertyTarget->getName()));
    }

    private function getTargetPropertyAttrIfEntityIsDeclared(string|\ReflectionProperty|\ReflectionParameter $property): ?TargetProperty
    {
        if (is_string($property)) {
            return null;
        }

        if (empty($targetPropertyAttr = $property->getAttributes(TargetProperty::class))) {
            return null;
        }

        $targetPropertyAttr = $targetPropertyAttr[0]->newInstance();
        if (null === $targetPropertyAttr->entity) {
            return null;
        }

        return $targetPropertyAttr;
    }

    private function getGetExistingEntityExpression(string $sourceVariableName, string|\ReflectionProperty $property, TargetProperty $targetPropertyAttr): string
    {
        if (null === ($entityClass = $targetPropertyAttr->entity)) {
            throw new \LogicException(sprintf('Entity class not defined for property %s.', is_string($property) ? $property : $property->getName()));
        }
        if (!class_exists($entityClass)) {
            throw new \LogicException(sprintf('Entity class %s not found.', $entityClass));
        }

        $getterExpression = $this->getGetterExpression($sourceVariableName, $property);

        return sprintf(self::GET_ENTITY_TEMPLATE, $this->entityManagerVariableName, $entityClass, $getterExpression);
    }

    private function getGetterExpression(string $sourceVariableName, string|\ReflectionProperty $property): string
    {
        if (is_string($property)) {
            return sprintf(self::ARRAY_PROPERTY_TEMPLATE, $sourceVariableName, $property);
        }

        if ($property->isPublic()) {
            return sprintf(self::PUBLIC_PROPERTY_TEMPLATE, $sourceVariableName, $property->isStatic() ? '::$' : '->', $property->getName());
        }

        foreach (self::GETTERS as $prefix) {
            $methodName = $prefix.!empty($prefix) ? ucfirst($property->getName()) : $property->getName();
            if ($property->getDeclaringClass()->hasMethod($methodName)) {
                $method = $property->getDeclaringClass()->getMethod($methodName);
                if (!$method->isPublic()) {
                    continue;
                }

                return sprintf(self::PRIVATE_PROPERTY_TEMPLATE, $sourceVariableName, $method->isStatic() ? '::' : '->', $methodName);
            }
        }

        throw new \LogicException(sprintf('Cannot find getter for property %s', $property->getName()));
    }

    private function getSetterExpression(string $sourceGetterExpresion, string $targetVariableName, string|\ReflectionProperty|\ReflectionParameter $argument): string
    {
        if (is_string($argument) || $targetVariableName === $this->targetArgumentsVariableName || $argument instanceof \ReflectionParameter) {
            return sprintf(
                self::ARRAY_ARGUMENT_TEMPLATE,
                $targetVariableName,
                $sourceGetterExpresion
            );
        }

        if ($argument->isPublic()) {
            return sprintf(
                self::ARGUMENT_TEMPLATE,
                sprintf(self::PUBLIC_PROPERTY_TEMPLATE, $targetVariableName, $argument->isStatic() ? '::$' : '->', $argument->getName()),
                $sourceGetterExpresion
            );
        }

        foreach (self::SETTERS as $prefix) {
            $methodName = $prefix.!empty($prefix) ? ucfirst($argument->getName()) : $argument->getName();
            if ($argument->getDeclaringClass()->hasMethod($methodName)) {
                $method = $argument->getDeclaringClass()->getMethod($methodName);
                if (!$method->isPublic()) {
                    continue;
                }

                return sprintf(
                    self::SETUP_TEMPLATE,
                    $targetVariableName,
                    $method->isStatic() ? '::' : '->',
                    $methodName,
                    $sourceGetterExpresion
                );
            }
        }

        throw new \LogicException(sprintf('Cannot find setter for property %s', $argument->getName()));
    }
}

class ArgumentExpressions
{
    public function __construct(
        public ?string $nameInSource,
        public ?string $nameInTarget,
        public string $setterExpression,
        public string $constructorExpression,
    ) {
    }
}
