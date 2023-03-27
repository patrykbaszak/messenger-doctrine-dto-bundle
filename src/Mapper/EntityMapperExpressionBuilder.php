<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\IgnorePropertiesIfNull;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\IgnorePropertyIfNull;
use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetProperty;
use PBaszak\MessengerDoctrineDTOBundle\Contract\PutObject;
use PBaszak\MessengerDoctrineDTOBundle\Utils\FindMatchingProperty;
use PBaszak\MessengerDoctrineDTOBundle\Utils\GetTargetEntity;

class EntityMapperExpressionBuilder
{
    use FindMatchingProperty;
    use GetTargetEntity;

    /** Property access templates */
    private const ARRAY_PROPERTY_TEMPLATE = '$%s[\'%s\']';
    private const PUBLIC_PROPERTY_TEMPLATE = '$%s%s%s';
    private const PRIVATE_PROPERTY_TEMPLATE = '$%s%s%s()';
    private const GET_ENTITY_TEMPLATE = '$this->%s->find(%s::class, %s)';
    private const PUT_OBJECT_TEMPLATE = '$this->handle(new %s(%s, %s::class))';

    /** Access methods prefixes */
    private const GETTERS = ['', 'get'];
    private const SETTERS = ['', 'set'];

    /** Expressions templates */
    private const ARRAY_ARGUMENT_TEMPLATE = '$%s[\'%s\']=%s;';
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
        private string $entityManagerVariableName = '_em',
        private bool $sourceAsArray = false,
        private bool $targetAsArray = false,
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
        $targetVariableName = $target->name === $this->sourceClass && $source->name !== $target->name ? $this->sourceVariableName : $this->targetVariableName;

        if ($sourceVariableName === $targetVariableName) {
            throw new \LogicException('Source and target variable names must be different.');
        }

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
            : (($entityClass = $this->getTargetEntityIfIsDeclared($sourceArgument))
                ? $this->getPutEntityExpression($sourceVariableName, $sourceArgument, $entityClass)
                : $this->getGetterExpression($sourceVariableName, $sourceArgument)
            );
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

        if ($source->name === $target->name) {
            $ignoreProperty = true;
        }

        if ($ignoreProperty) {
            if (!str_ends_with($getterExpression, ')')) {
                $setterExpression = sprintf(
                    'if (isset(%s) && null !== %s) { %s }',
                    $getterExpression,
                    $getterExpression,
                    $setterExpression
                );
                $constructorExpression = sprintf(
                    'if (isset(%s) && null !== %s) { %s }',
                    $getterExpression,
                    $getterExpression,
                    $constructorExpression
                );
            } else {
                $setterExpression = sprintf(
                    'if (null !== %s) { %s }',
                    $getterExpression,
                    $setterExpression
                );
                $constructorExpression = sprintf(
                    'if (null !== %s) { %s }',
                    $getterExpression,
                    $constructorExpression
                );
            }
        }

        return new ArgumentExpressions(
            is_string($sourceArgument) ? $sourceArgument : $sourceArgument->getName(),
            is_string($targetArgument) ? $targetArgument : $targetArgument->getName(),
            $setterExpression,
            $constructorExpression,
        );
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

    private function getPutEntityExpression(string $sourceVariableName, string|\ReflectionProperty $property, string $targetObjectClass): string
    {
        if (!class_exists($targetObjectClass)) {
            throw new \LogicException(sprintf('Entity or DTO class %s not found.', $targetObjectClass));
        }

        $getterExpression = $this->getGetterExpression($sourceVariableName, $property);

        return sprintf(self::PUT_OBJECT_TEMPLATE, PutObject::class, $getterExpression, $targetObjectClass);
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
        if (is_string($property) || $this->sourceAsArray) {
            return sprintf(self::ARRAY_PROPERTY_TEMPLATE, $sourceVariableName, is_string($property) ? $property : $property->getName());
        }

        if ($property->isPublic()) {
            return sprintf(self::PUBLIC_PROPERTY_TEMPLATE, $sourceVariableName, $property->isStatic() ? '::$' : '->', $property->getName());
        }

        foreach (self::GETTERS as $prefix) {
            $methodName = $prefix.(!empty($prefix) ? ucfirst($property->getName()) : $property->getName());
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
        if (is_string($argument) || $targetVariableName === $this->targetArgumentsVariableName || $argument instanceof \ReflectionParameter || $this->targetAsArray) {
            return sprintf(
                self::ARRAY_ARGUMENT_TEMPLATE,
                $targetVariableName,
                is_string($argument) ? $argument : $argument->getName(),
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
