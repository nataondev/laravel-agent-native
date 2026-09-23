<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Attributes;

use Attribute;

/**
 * Opt-in marker enabling whole-class action discovery.
 *
 * When present on a class, every public method without an explicit ignore
 * is exposed as an agent action (methods with #[AgentAction] keep their
 * explicit metadata). Useful for service classes written agent-first.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final readonly class AgentExpose
{
    public function __construct(
        public string $description = '',
    ) {}
}
