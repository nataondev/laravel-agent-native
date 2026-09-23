<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Tests\Fixtures;

use AgentNative\Laravel\Attributes\AgentAction;
use AgentNative\Laravel\Livewire\InteractsWithAgent;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class Cart extends Component
{
    use InteractsWithAgent;

    /** @var list<array{productId: int, quantity: int}> */
    public array $items = [];

    public int $lastAdded = 0;

    #[AgentAction(description: 'Add a product to the shopping cart')]
    public function addItem(int $productId, int $quantity = 1): void
    {
        $this->items[] = ['productId' => $productId, 'quantity' => $quantity];
        $this->lastAdded = $productId;
    }

    #[AgentAction(description: 'Empty the cart')]
    public function clear(): void
    {
        $this->items = [];
    }

    public function render(): View
    {
        return view('agent-native-test::cart');
    }
}
