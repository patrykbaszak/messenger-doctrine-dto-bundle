<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\DTO;

class DQLProperty
{
    public readonly string $name;
    public readonly ?string $outputName;

    public function __construct(
        string $name,
        ?string $outputName = null
    ) {
        $this->name = $name;
        $this->outputName = $name === $outputName ? null : $outputName;
    }
}
