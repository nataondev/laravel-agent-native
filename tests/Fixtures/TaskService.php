<?php

declare(strict_types=1);

namespace AgentNative\Laravel\Tests\Fixtures;

use AgentNative\Laravel\Attributes\AgentAction;
use AgentNative\Laravel\Attributes\AgentParam;

enum Priority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
}

enum Status
{
    case Open;
    case Closed;
}

class TaskService
{
    public function __construct(private readonly TaskRepository $repository) {}

    /**
     * Create a new task in the backlog.
     *
     * @param  string  $title  The task title
     * @param  string[]  $tags  Labels to attach
     */
    #[AgentAction(description: 'Create a task in the backlog')]
    public function createTask(
        string $title,
        #[AgentParam(description: 'Priority level for triage')]
        Priority $priority = Priority::Medium,
        ?int $estimateHours = null,
        array $tags = [],
        bool $notify = false,
        float $weight = 1.0,
    ): array {
        return $this->repository->store([
            'title' => $title,
            'priority' => $priority->value,
            'estimateHours' => $estimateHours,
            'tags' => $tags,
            'notify' => $notify,
            'weight' => $weight,
        ]);
    }

    #[AgentAction(description: 'Rename an existing task', name: 'rename_task')]
    public function rename(
        #[AgentParam(description: 'The task id', required: true)]
        int $id,
        #[AgentParam(description: 'New title')]
        string $title,
    ): string {
        return "Task {$id} renamed to {$title}";
    }

    #[AgentAction(description: 'Close a task')]
    public function close(int $id, Status $status = Status::Closed): string
    {
        return "Task {$id} is now {$status->name}";
    }

    /**
     * @param  int[]  $ids
     */
    #[AgentAction(description: 'Delete tasks in bulk')]
    public function deleteMany(array $ids): int
    {
        return count($ids);
    }

    public function notAnAction(): string
    {
        return 'hidden';
    }
}

class TaskRepository
{
    /** @var list<array<string, mixed>> */
    public array $stored = [];

    /** @param array<string, mixed> $data */
    public function store(array $data): array
    {
        $this->stored[] = $data;

        return $data;
    }
}
