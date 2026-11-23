<?php

declare(strict_types=1);

namespace App\Domain\Operations\DTOs;

/**
 * Outcome of dispatching a smoke job and waiting for a live worker to consume it.
 */
final readonly class QueueWorkerCheckDTO
{
    public function __construct(
        public bool $succeeded,
        public string $queue,
        public int $waitedMs,
        public string $checkedAt,
    ) {
    }

    /**
     * @return array{succeeded: bool, queue: string, waited_ms: int, checked_at: string}
     */
    public function toArray(): array
    {
        return [
            'succeeded' => $this->succeeded,
            'queue' => $this->queue,
            'waited_ms' => $this->waitedMs,
            'checked_at' => $this->checkedAt,
        ];
    }

    /**
     * @param array{succeeded: bool, queue: string, waited_ms: int, checked_at: string} $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values['succeeded'], $values['queue'], $values['waited_ms'], $values['checked_at']);
    }
}
