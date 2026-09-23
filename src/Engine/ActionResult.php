<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Engine;

/**
 * Outcome of an agent tool invocation. Always JSON-safe; never throws.
 */
final readonly class ActionResult implements \JsonSerializable
{
    private function __construct(
        public bool $ok,
        public string $action,
        public mixed $output,
        public ?string $error,
        public ?string $code,
    ) {}

    public static function success(string $action, mixed $output): self
    {
        return new self(true, $action, $output, null, null);
    }

    public static function error(string $action, string $code, string $message): self
    {
        return new self(false, $action, null, $message, $code);
    }

    /** @return array{ok: bool, action: string, output?: mixed, error?: string, code?: string} */
    public function jsonSerialize(): array
    {
        $payload = ['ok' => $this->ok, 'action' => $this->action];

        if ($this->ok) {
            $payload['output'] = $this->output;
        } else {
            $payload['error'] = $this->error;
            $payload['code'] = $this->code;
        }

        return $payload;
    }
}
