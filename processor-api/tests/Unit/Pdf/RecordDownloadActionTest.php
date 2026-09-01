<?php

namespace Tests\Unit\Pdf;

use App\Application\Engagement\Actions\RecordDownloadAction;
use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Domain\Engagement\DTOs\DownloadCreateDTO;
use App\Domain\Engagement\DTOs\DownloadFilterDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\UserId;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class RecordDownloadActionTest extends TestCase
{
    public function test_record_creates_download_for_viewer_and_entity(): void
    {
        $repository = new FakeDownloadRepository;
        $action = new RecordDownloadAction($repository);
        $viewer = $this->viewer();

        $action->record($viewer, ObjectTemplateType::ARTICLE, 321);

        $this->assertCount(1, $repository->created);
        $this->assertSame(123, $repository->created[0]->userId);
        $this->assertSame(ObjectTemplateType::ARTICLE->getLegacyId(), $repository->created[0]->templateId);
        $this->assertSame(321, $repository->created[0]->realObjectId);
    }

    public function test_record_logs_and_does_not_throw_when_tracking_fails(): void
    {
        Log::spy();

        $repository = new FakeDownloadRepository;
        $repository->shouldFail = true;
        $action = new RecordDownloadAction($repository);

        $action->record(
            viewerId: $this->viewer(),
            objectType: ObjectTemplateType::LIST,
            entityId: 456,
            context: ['source' => 'catalogue', 'kind' => 'words'],
        );

        $this->assertSame(1, $repository->attempts);

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('PDF download tracking failed', Mockery::on(function (array $context): bool {
                return $context['source'] === 'catalogue'
                    && $context['kind'] === 'words'
                    && $context['user_id'] === 123
                    && $context['entity_id'] === 456
                    && $context['object_type'] === ObjectTemplateType::LIST->value
                    && $context['error'] === 'Download tracking failed';
            }));
    }

    private function viewer(): UserId
    {
        return UserId::from(123);
    }
}

class FakeDownloadRepository implements DownloadRepositoryInterface
{
    /** @var list<DownloadCreateDTO> */
    public array $created = [];

    public int $attempts = 0;

    public bool $shouldFail = false;

    public function create(DownloadCreateDTO $data): void
    {
        $this->attempts++;

        if ($this->shouldFail) {
            throw new RuntimeException('Download tracking failed');
        }

        $this->created[] = $data;
    }

    public function findByFilter(DownloadFilterDTO $filter): ?int
    {
        return null;
    }

    public function deleteByEntity(int $entityId, int $entityTypeId): void
    {
    }

    public function findAllByEntityIds(array $entityIds, ObjectTemplateType $objectType): array
    {
        return [];
    }

    public function findAllByFilter(DownloadFilterDTO $filter): array
    {
        return [];
    }

    public function countByFilter(DownloadFilterDTO $filter): int
    {
        return 0;
    }
}
