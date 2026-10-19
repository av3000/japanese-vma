<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Repositories;

use App\Application\Activity\Interfaces\ActivityRecorderInterface;
use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Activity\Enums\ActivitySource;
use App\Infrastructure\Persistence\Models\AdminActivity;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

final class DatabaseActivityRecorder implements ActivityRecorderInterface
{
    public function __construct(
        private readonly AuthFactory $auth,
    ) {
    }

    public function record(ActivityEntry $entry): void
    {
        $changes = $entry->loggableChanges();

        AdminActivity::query()->create([
            'actor_id' => $entry->actorId,
            'actor_name' => $entry->actorName,
            'action' => $entry->action,
            'subject_type' => $entry->subjectType,
            'subject_id' => $entry->subjectId,
            'subject_label' => $entry->subjectLabel === null ? null : mb_substr($entry->subjectLabel, 0, 255),
            'changes' => $changes['before'] === [] && $changes['after'] === [] ? null : $changes,
            'source' => $this->source(),
            'context' => $entry->context === [] ? null : $entry->context,
        ]);
    }

    /**
     * The API authenticates with a Passport bearer token on `api`; only the Filament panel signs in
     * on the `web` session guard. The guard tells the surface apart without services having to
     * pass transport details down. A bearer token is checked first: a request carrying one is an
     * API call whatever session cookie came with it.
     */
    private function source(): ActivitySource
    {
        if ($this->auth->guard('api')->check()) {
            return ActivitySource::Api;
        }

        if ($this->auth->guard('web')->check()) {
            return ActivitySource::Panel;
        }

        return app()->runningInConsole() ? ActivitySource::Console : ActivitySource::Api;
    }
}
