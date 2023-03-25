<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Utils;

trait GetIdentifier
{
    private const IDENTIFIER_METHODS = [
        'getId',
        'getUuid',
        'id',
        'uuid',
        '__get',
        '__call',
        'getIdentifier',
    ];

    /**
     * @param array<string,mixed>|object $data Representation of dto
     */
    public function getIdentifier(array|object $data): null|int|string
    {
        if (is_array($data)) {
            return $data['id'] ?? $data['uuid'] ?? null;
        }

        if (isset($data->id)) {
            return $data->id;
        }

        if (isset($data->uuid)) {
            return $data->uuid;
        }

        foreach (self::IDENTIFIER_METHODS as $method) {
            if (method_exists($data, $method)) {
                $result = $data->$method();
                if (null !== $result) {
                    return $result;
                }
            }
        }

        return null;
    }
}
