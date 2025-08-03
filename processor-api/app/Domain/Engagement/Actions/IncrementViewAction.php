<?php
namespace App\Domain\Engagement\Actions;

use App\Domain\Articles\Models\Article;
use App\Http\Models\ObjectTemplate;
use App\Domain\Articles\Models\View;

class IncrementViewAction
{
    /**
     * Track article views for all users - authenticated and anonymous.
     * For authenticated users, we use their user_id as the primary identifier.
     * For anonymous users, we rely on IP address, though this isn't perfect
     * due to shared networks and changing IPs.
     */
    public function execute(Article $article): void
    {
        $objectTemplateId = ObjectTemplate::where('title', 'article')->first()->id;
        $userId = auth()->id();
        $userIp = request()->ip();

        // For authenticated users, check by user_id
        // For anonymous users, check by IP address
        $existingView = View::where('template_id', $objectTemplateId)
            ->where('real_object_id', $article->id)
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
                'real_object_id' => $article->id,
            ]);
        }
    }
}
