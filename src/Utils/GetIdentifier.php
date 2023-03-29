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

        if (method_exists($data, '__get')) {
            return $data->__get('id')
                ?? $data->__get('uuid')
                ?? $data->__get('identifier')
                ?? null;
        }

        if (method_exists($data, '__call')) {
            return $data->__call('getId', [])
                ?? $data->__call('id', [])
                ?? $data->__call('getUuid', [])
                ?? $data->__call('uuid', [])
                ?? $data->__call('getIdentifier', [])
                ?? null;
        }

        return null;
    }
}
