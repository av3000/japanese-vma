<?php
namespace App\Application\Engagement\Actions;

use App\Domain\Shared\Enums\ObjectTemplateType;
use App\Http\Models\ObjectTemplate;
use App\Infrastructure\Persistence\Models\View;

class IncrementViewAction
{
    /**
     * Track entity views for all users - authenticated and anonymous.
     * For authenticated users, we use their user_id as the primary identifier.
     * For anonymous users, we rely on IP address, though this isn't perfect
     * due to shared networks and changing IPs.
     */
    public function execute(int $id, ObjectTemplateType $objectTemplateType): void
    {
        $objectTemplateTypeValue = $objectTemplateType->value;
        $objectTemplateId = ObjectTemplate::where('title', $objectTemplateTypeValue)->first()->id;
        $userId = auth()->id();
        $userIp = request()->ip();

        // For authenticated users, check by user_id
        // For anonymous users, check by IP address
        $existingView = View::where('template_id', $objectTemplateId)
            ->where('real_object_id', $id)
            ->when($userId, function ($query) use ($userId) {
                return $query->where('user_id', $userId);
            }, function ($query) use ($userIp) {
                return $query->where('user_ip', $userIp)->whereNull('user_id');
            })
            ->first();

        if ($existingView) {
            // Update the timestamp to track the latest view
            $existingView->touch();
        } else {
            View::create([
                'user_id' => $userId, // Will be null for anonymous users
                'user_ip' => $userIp,
                'template_id' => $objectTemplateId,
                'real_object_id' => $id,
            ]);
        }
    }
}
