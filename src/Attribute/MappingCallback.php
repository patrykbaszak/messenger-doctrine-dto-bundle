<?php

declare(strict_types=1);

namespace PBaszak\MessengerDoctrineDTOBundle\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class MappingCallback
{
    /**
     * @example '(new DateTime(%s))->format("Y-m-d")' - Do not forget to add %s to the callback.
     * The callback will be run on the target property with eval() function. So it should be a valid PHP code.
     * <code>
     * foreach ($this->sortCallbacks($property->callbacks) as $callback) {
     *     $getter = sprintf($callback->callback, $getter);
     * }
     * </code>
     *
     * @param int $priority - priority of the callback - if higher then callback will be run earlier
     */
    public function __construct(
        public readonly string $callback,
        public readonly int $priority = 0,
    ) {
    }
}
