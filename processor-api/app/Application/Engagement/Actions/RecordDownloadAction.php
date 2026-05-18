<?php

namespace App\Application\Engagement\Actions;

use App\Application\Engagement\Interfaces\Repositories\DownloadRepositoryInterface;
use App\Domain\Engagement\DTOs\DownloadCreateDTO;
use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordDownloadAction
{
    public function __construct(
        private readonly DownloadRepositoryInterface $downloadRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     */
    public function record(User $viewer, ObjectTemplateType $objectType, int $entityId, array $context = []): void
    {
        try {
            $this->downloadRepository->create(new DownloadCreateDTO(
                userId: $viewer->id,
                templateId: $objectType->getLegacyId(),
                realObjectId: $entityId,
            ));
        } catch (Throwable $exception) {
            Log::warning('PDF download tracking failed', array_merge($context, [
                'object_type' => $objectType->value,
                'entity_id' => $entityId,
                'user_id' => $viewer->id,
                'error' => $exception->getMessage(),
            ]));
        }
    }
}
