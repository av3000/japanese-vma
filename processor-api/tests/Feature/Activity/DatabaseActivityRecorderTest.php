<?php

declare(strict_types=1);

namespace Tests\Feature\Activity;

use App\Application\Activity\ActivitySourceContext;
use App\Domain\Activity\DTOs\ActivityEntry;
use App\Domain\Activity\Enums\ActivityAction;
use App\Domain\Activity\Enums\ActivitySource;
use App\Domain\Activity\Enums\ActivitySubjectType;
use App\Infrastructure\Persistence\Models\AdminActivity;
use App\Infrastructure\Persistence\Repositories\DatabaseActivityRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use LogicException;
use Tests\TestCase;

/**
 * The recorder fails loudly outside production when an entry would lose or leak audit data, and
 * keeps the row (logging the problem) in production.
 */
final class DatabaseActivityRecorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_source_comes_from_the_context(): void
    {
        $this->recorder(ActivitySource::Panel)->record($this->entry());

        self::assertSame(ActivitySource::Panel, AdminActivity::query()->sole()->source);
    }

    public function test_a_source_on_the_entry_wins_over_the_context(): void
    {
        $this->recorder(ActivitySource::Panel)->record($this->entry(source: ActivitySource::Console));

        self::assertSame(ActivitySource::Console, AdminActivity::query()->sole()->source);
    }

    public function test_a_missing_source_throws_when_strict(): void
    {
        $this->expectException(LogicException::class);

        $this->recorder(null)->record($this->entry());
    }

    public function test_a_missing_source_is_logged_and_stored_as_api_when_lenient(): void
    {
        Log::spy();

        $this->recorder(null, strict: false)->record($this->entry());

        self::assertSame(ActivitySource::Api, AdminActivity::query()->sole()->source);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_an_unknown_snapshot_key_throws_when_strict(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('titel');

        $this->recorder(ActivitySource::Panel)->record($this->entry(before: ['titel' => 'typo']));
    }

    public function test_an_unknown_snapshot_key_is_dropped_with_a_warning_when_lenient(): void
    {
        Log::spy();

        $this->recorder(ActivitySource::Panel, strict: false)->record($this->entry(before: ['title' => 'Kept', 'titel' => 'typo']));

        self::assertSame(['before' => ['title' => 'Kept'], 'after' => []], AdminActivity::query()->sole()->changes);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_secret_context_key_throws_when_strict(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->recorder(ActivitySource::Panel)->record($this->entry(context: ['password' => 'hunter2']));
    }

    public function test_a_secret_context_key_is_dropped_when_lenient_and_flags_are_kept(): void
    {
        Log::spy();

        $this->recorder(ActivitySource::Panel, strict: false)->record($this->entry(context: ['password' => 'hunter2', 'password_changed' => true]));

        self::assertSame(['password_changed' => true], AdminActivity::query()->sole()->context);
    }

    private function recorder(?ActivitySource $context, bool $strict = true): DatabaseActivityRecorder
    {
        return new DatabaseActivityRecorder(new ActivitySourceContext($context), $strict);
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, scalar|array<mixed>|null> $context
     */
    private function entry(array $before = [], array $context = [], ?ActivitySource $source = null): ActivityEntry
    {
        return new ActivityEntry(
            actorId: null,
            actorName: 'system',
            action: ActivityAction::ArticleDeleted,
            subjectType: ActivitySubjectType::Article,
            subjectId: 'article-uuid',
            before: $before,
            context: $context,
            source: $source,
        );
    }
}
