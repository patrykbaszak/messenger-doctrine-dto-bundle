<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils;

use PBaszak\MessengerDoctrineDTOBundle\Attribute\TargetProperty;

trait FindMatchingProperty
{
    protected function findMatchingProperty(\ReflectionClass $propertySource, \ReflectionClass $propertyTarget, string|\ReflectionProperty|\ReflectionParameter $property): null|\ReflectionProperty // + maybe string|\ReflectionParameter
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
}
