<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Attributes;

use Attribute;

/**
 * Describes a single action parameter for the agent.
 *
 * Overrides what reflection can infer: without this attribute the parameter
 * description falls back to the docblock `@param` tag. Requiredness is only
 * overridden when explicitly passed; otherwise it is inferred from the
 * signature (nullable type or default value => optional).
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class AgentParam
{
    public function __construct(
        public string $description = '',
        public ?bool $required = null,
    ) {}
}
