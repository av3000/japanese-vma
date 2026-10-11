<?php

declare(strict_types=1);

namespace App\Filament\Resources\StudyMaterial\Sentences\Pages;

use App\Application\Auth\DTOs\AuthenticatedUser;
use App\Application\JapaneseMaterial\Sentences\Interfaces\Repositories\SentenceRepositoryInterface;
use App\Application\JapaneseMaterial\Sentences\Policies\SentencePolicy;
use App\Application\JapaneseMaterial\Sentences\Services\SentenceServiceInterface;
use App\Domain\JapaneseMaterial\Sentences\DTOs\SentenceWriteDTO;
use App\Domain\Shared\ValueObjects\EntityId;
use App\Filament\Resources\StudyMaterial\Sentences\SentenceResource;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Infrastructure\Persistence\Models\Sentence;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewSentence extends ViewRecord
{
    protected static string $resource = SentenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('editSentence')
                ->label('Edit')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->visible(fn (): bool => $this->may('canUpdate'))
                ->fillForm(fn (): array => ['content' => $this->sentence()->content])
                ->schema([
                    Textarea::make('content')
                        ->label('Sentence')
                        ->required()
                        ->maxLength(300)
                        ->rows(3)
                        ->helperText('Kanji and word links are derived again from the new text.'),
                ])
                ->action(function (array $data): void {
                    $result = app(SentenceServiceInterface::class)->update(
                        new EntityId((string) $this->sentence()->uuid),
                        new SentenceWriteDTO(trim((string) $data['content'])),
                        PanelActor::current(),
                    );

                    if (ResultNotifications::send($result, 'Sentence updated.')) {
                        $this->sentence()->refresh();
                    }
                }),
            Action::make('deleteSentence')
                ->label('Delete')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('The sentence, its kanji and word links, comments and list entries are removed. This cannot be undone.')
                ->visible(fn (): bool => $this->may('canDelete'))
                ->action(function (): void {
                    $result = app(SentenceServiceInterface::class)->delete(
                        new EntityId((string) $this->sentence()->uuid),
                        PanelActor::current(),
                    );

                    if (ResultNotifications::send($result, 'Sentence deleted.')) {
                        $this->redirect(SentenceResource::getUrl('index'));
                    }
                }),
        ];
    }

    /**
     * Panel permission plus the application SentencePolicy, which keeps imported rows immutable
     * for everyone and lets moderators act on user-authored ones.
     */
    private function may(string $rule): bool
    {
        if ($this->sentence()->user_id === null) {
            return false;
        }

        return PanelActor::may('sentences.moderate', function (AuthenticatedUser $actor) use ($rule): bool {
            $domain = app(SentenceRepositoryInterface::class)->findByUuid(new EntityId((string) $this->sentence()->uuid));

            return $domain !== null && app(SentencePolicy::class)->{$rule}($actor, $domain);
        });
    }

    private function sentence(): Sentence
    {
        /** @var Sentence $record */
        $record = $this->getRecord();

        return $record;
    }
}
