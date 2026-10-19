<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Sentences\Pages;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Sentences\Policies\SentenceModerationPolicy;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceModerationServiceInterface;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\StudyMaterial\Sentences\SentenceResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\Sentence;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

/**
 * Staff remove user-authored sentences but never rewrite them; only the author edits a sentence.
 * Imported sentences offer no action.
 */
class ViewSentence extends ViewRecord
{
    protected static string $resource = SentenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deleteSentence')
                ->label('Delete')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('The sentence, its kanji and word links, comments and list entries are removed. This cannot be undone.')
                ->visible(fn (): bool => $this->sentence()->user_id !== null && PanelActor::may(
                    'sentences.moderate',
                    static fn (AuthenticatedUser $actor): bool => app(SentenceModerationPolicy::class)->canModerate($actor),
                ))
                ->action(function (): void {
                    $result = app(SentenceModerationServiceInterface::class)->remove(
                        new EntityId((string) $this->sentence()->uuid),
                        PanelActor::current(),
                    );

                    if (ResultNotifications::send($result, 'Sentence deleted.')) {
                        $this->redirect(SentenceResource::getUrl('index'));
                    }
                }),
        ];
    }

    private function sentence(): Sentence
    {
        /** @var Sentence $record */
        $record = $this->getRecord();

        return $record;
    }
}
