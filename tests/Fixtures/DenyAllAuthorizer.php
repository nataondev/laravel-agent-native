<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Tests\Fixtures;

use Illuminate\Http\Request;

class DenyAllAuthorizer
{
    public function authorize(Request $request, ?string $action): bool
    {
        return false;
    }
}
