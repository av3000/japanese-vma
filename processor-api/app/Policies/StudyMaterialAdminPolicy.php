<?php

declare(strict_types=1);

namespace App\Policies;

use App\Application\Users\Support\PanelPermission;
use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Kanji, radicals, words and sentences in the panel. Imported dictionary rows are read-only until
 * a correction strategy that survives the importer exists. User-authored sentences are removed,
 * never edited, through SentenceModerationServiceInterface, which applies
 * SentenceModerationPolicy.
 */
class StudyMaterialAdminPolicy extends PanelResourcePolicy
{
    protected function viewPermission(): PanelPermission
    {
        return PanelPermission::StudyView;
    }

    protected function updatePermission(): ?PanelPermission
    {
        return null;
    }

    protected function deletePermission(): ?PanelPermission
    {
        return PanelPermission::SentencesModerate;
    }

    /**
     * Only a user-authored sentence can be removed; imported rows belong to the importer.
     */
    public function delete(User $user, Model $record): bool
    {
        return $record instanceof Sentence
            && $record->user_id !== null
            && parent::delete($user, $record);
    }
}
