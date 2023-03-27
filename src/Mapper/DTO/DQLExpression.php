<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\DTO;

class DQLExpression
{
    /**
     * @param DQLProperty[] $properties
     */
    public function __construct(
        public readonly array $properties,
        public readonly string $alias,
        public readonly string $entityClass,
        public readonly ?string $parentAlias = null,
        public readonly string $joinType = 'LEFT'
    ) {
    }

    public function getFromExpression(): string
    {
        return $this->entityClass.' '.$this->alias;
    }

    public function getJoinExpression(): ?string
    {
        if (!$this->parentAlias) {
            return null;
        }

        return $this->joinType.' JOIN '.$this->parentAlias.'.'.$this->alias.' '.$this->alias;
    }

    /**
     * @return string[]
     */
    public function getPropertiesExpressions(): array
    {
        $output = [];

        if (!$this->parentAlias) {
            foreach ($this->properties as $property) {
                if ($property->outputName) {
                    $output[] = $this->alias.'.'.$property->name.' as '.$property->outputName;
                } else {
                    $output[] = $this->alias.'.'.$property->name;
                }
            }
        } else {
            foreach ($this->properties as $property) {
                if ($property->outputName) {
                    $output[] = $this->alias.'.'.$property->name.' as '.$this->alias.'__'.$property->outputName;
                } else {
                    $output[] = $this->alias.'.'.$property->name.' as '.$this->alias.'__'.$property->name;
                }
            }
        }

        return $output;
    }

    /**
     * @return DQLProperty[]
     */
    public function getPropertiesWithOutputNames(): array
    {
        if (!$this->parentAlias) {
            foreach ($this->properties as $property) {
                if ($property->outputName) {
                    $property->path = $property->outputName;
                } else {
                    $property->path = $property->name;
                }
            }
        } else {
            foreach ($this->properties as $property) {
                if ($property->outputName) {
                    $property->path = $this->alias.'__'.$property->outputName;
                } else {
                    $property->path = $this->alias.'__'.$property->name;
                }
            }
        }

        return $this->properties;
    }
}
