<?php

declare(strict_types=1);

namespace App\Policies;

use App\Infrastructure\Persistence\Models\Sentence;
use App\Infrastructure\Persistence\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Kanji, radicals, words and sentences in the panel. Imported dictionary rows are read-only until
 * a correction strategy that survives the importer exists; only user-authored sentences can be
 * edited or deleted, and those writes also pass the application SentencePolicy.
 */
class StudyMaterialAdminPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPanelPermission('study.view');
    }

    public function view(User $user, Model $record): bool
    {
        return $user->hasPanelPermission('study.view');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $record): bool
    {
        return $record instanceof Sentence
            && $record->user_id !== null
            && $user->hasPanelPermission('sentences.moderate');
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->update($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
