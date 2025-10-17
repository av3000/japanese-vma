<?php
namespace App\Application\Engagement\Actions;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Domain\Shared\ValueObjects\{Viewer};
use App\Http\Models\ObjectTemplate;
use App\Infrastructure\Persistence\Models\View;
use App\Application\Engagement\Interfaces\Repositories\ViewRepositoryInterface;
use App\Domain\Engagement\DTOs\{ViewCreateDTO, ViewFilterDTO};

class IncrementViewAction
{
    public function __construct(
        private ViewRepositoryInterface $viewRepository
    ) {}
    /**
     * Track entity views for all users - authenticated and anonymous.
     * For authenticated users, we use their user_id as the primary identifier.
     * For anonymous users, we rely on IP address, though this isn't perfect
     * due to shared networks and changing IPs.
     */
    public function execute(
        int $id,
        ObjectTemplateType $objectType,
        Viewer $viewer
    ): void {
        $existingViewId = $this->viewRepository->findByFilter(new ViewFilterDTO(
            entityId: $id,
            objectType: $objectType,
            userId: $viewer->userId(),
            ipAddress: $viewer->ipAddress()
        ));

        if ($existingViewId) {
            $this->viewRepository->updateTimestampById($existingViewId);
        } else {
            // TODO: Consider adding a check to prevent multiple views from the same IP within a short timeframe to avoid inflation. We check if view exists, but we update timestamp if it does all the time.
             // TODO: Consider using a job/queue for this to reduce request time impact.
            $this->viewRepository->create(new ViewCreateDTO(
                userId: $viewer->userId(),
                userIp: $viewer->ipAddress(),
                templateId: $objectType->getLegacyId(),
                realObjectId: $id,
            ));
        }
    }
}
