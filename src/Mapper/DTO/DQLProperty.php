<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Mapper\DTO;

class DQLProperty
{
    public readonly ?string $outputName;
    public string $path;

    public function __construct(
        public readonly string $name,
        ?string $outputName = null,
        public readonly array $callbacks = [],
    ) {
        $this->outputName = $name === $outputName ? null : $outputName;
    }
}
