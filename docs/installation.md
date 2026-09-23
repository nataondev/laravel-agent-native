# Installation

## Requirements

- PHP **8.2+**
- Laravel **11+**
- Livewire **3+** (optional — only for the Livewire bridge)

## Install

```bash
composer require nataondev/laravel-agent-native
```

The service provider is auto-discovered. Publish the config:

```bash
php artisan vendor:publish --tag=agent-native-config
```

## Register action classes

List every class carrying `#[AgentAction]` methods (or `#[AgentExpose]`) in `config/agent-native.php`:

```php
'classes' => [
    App\Services\TaskService::class,
    App\Livewire\Cart::class,
],
```

Verify everything wired up:

```bash
php artisan agent:inspect
```

Next: [Defining actions](defining-actions.md)
