<?php

declare(strict_types=1);

namespace App\Filament\Pages\Translations;

use App\Application\Translations\Services\TranslationEditorService;
use App\Application\Translations\Services\TranslationEditorServiceInterface;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Translations\DTOs\TranslationRowDTO;
use App\Domain\Translations\Enums\TranslationState;
use App\Domain\Translations\Models\TranslationLocale;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use App\Shared\Results\Result;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;

/**
 * One label, next to its English source: pick a language and a key, type the translation, save, and
 * it is live. A blank save clears the override, so the label falls back to English again.
 *
 * Talks to `TranslationEditorServiceInterface` only, which also refuses English, a deactivated
 * language and a key that is no longer in the catalogue, so a page that was left open while any of
 * those changed writes nothing. Anyone with `translations.view` may open it; only
 * `translations.update` may save.
 */
class TranslationEditor extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $title = 'Edit translation';

    protected static ?string $slug = 'translations/edit';

    /** The code of the language being worked on. Null once it turned out to be inactive. */
    #[Url]
    public ?string $locale = null;

    /** Dots in a key keep it out of a path segment, so it travels as a query parameter. */
    #[Url(as: 'key')]
    public ?string $translationKey = null;

    /** What the translator has typed. Blank means "use English". */
    public string $translation = '';

    /** @var list<TranslationRowDTO>|null Every key of `$rowsFor`, read once per request: the selects and the preview all need it. */
    private ?array $rows = null;

    private ?string $rowsFor = null;

    public static function canAccess(): bool
    {
        return PanelActor::may(PanelPermission::TranslationsView);
    }

    public static function canUpdate(): bool
    {
        return PanelActor::may(PanelPermission::TranslationsUpdate);
    }

    public function mount(): void
    {
        $codes = $this->editableCodes();

        if (! in_array($this->locale, $codes, true)) {
            $this->locale = $codes[0] ?? null;
        }

        $this->loadTranslation();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToOverview')
                ->label('Back to overview')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->color('gray')
                ->url(fn (): string => TranslationOverview::getUrl($this->locale === null ? [] : ['locale' => $this->locale])),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Save translation')
                            ->submit('save')
                            ->keyBindings(['mod+s'])
                            ->visible(fn (): bool => self::canUpdate())
                            ->disabled(fn (): bool => $this->locale === null || $this->translationKey === null),
                    ])->key('form-actions'),
                ]),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $hasKey = fn (): bool => $this->translationKey !== null;

        return $schema->components([
            Select::make('locale')
                ->label('Language')
                ->options(fn (): array => $this->localeOptions())
                ->placeholder('Choose an active language')
                ->live()
                ->afterStateUpdated(fn () => $this->loadTranslation()),
            Select::make('translationKey')
                ->label('Find a key')
                ->options(fn (): array => $this->keyOptions())
                ->searchable()
                ->placeholder('Search by key or English text')
                ->live()
                ->afterStateUpdated(fn () => $this->loadTranslation()),
            Section::make('English source')
                ->description('Read only. English changes only through a pull request.')
                ->schema([
                    TextEntry::make('sourceKey')
                        ->label('Key')
                        ->fontFamily('mono')
                        ->state(fn (): ?string => $this->translationKey),
                    TextEntry::make('sourceText')
                        ->label('English')
                        ->state(fn (): ?string => $this->currentRow()?->source),
                ])
                ->visible($hasKey),
            Textarea::make('translation')
                ->label('Translation')
                ->helperText('Leave empty to use English')
                ->maxLength(TranslationEditorService::MAX_VALUE_LENGTH)
                ->rows(5)
                ->live(debounce: 400)
                ->disabled(fn (): bool => ! self::canUpdate())
                ->visible($hasKey),
            TextEntry::make('fallbackPreview')
                ->label('Preview while empty')
                ->state(fn (): ?string => $this->currentRow()?->source)
                ->visible(fn (): bool => $this->translationKey !== null && trim($this->translation) === ''),
        ]);
    }

    public function save(): void
    {
        abort_unless(self::canUpdate(), 403);

        if ($this->locale === null || $this->translationKey === null) {
            Notification::make()->warning()->title('Choose a language and a key first.')->send();

            return;
        }

        $result = $this->service()->saveOverride(
            $this->locale,
            $this->translationKey,
            $this->translation,
            PanelActor::user()->getKey(),
        );

        if ($result->isFailure()) {
            $this->reportFailure($result);

            return;
        }

        $this->translation = trim($this->translation);
        $this->rows = null;

        Notification::make()
            ->success()
            ->title($result->getData() === TranslationState::Translated
                ? 'Saved. Live on the next page load.'
                : 'Cleared. English shows again, live on the next page load.')
            ->send();
    }

    /**
     * A save that the service refused wrote nothing. Say why, and put the page back on something
     * that can be saved.
     */
    private function reportFailure(Result $result): void
    {
        switch ($result->getError()->code) {
            case 'Translations.ValueTooLong':
            case 'Translations.InvalidValue':
                throw ValidationException::withMessages(['translation' => ResultNotifications::message($result)]);

            case 'Translations.LocaleInactive':
            case 'Translations.LocaleNotFound':
                $this->locale = null;
                $this->translation = '';
                Notification::make()->danger()->title('This language is no longer active. Choose an active language.')->send();

                return;

            case 'Translations.UnknownKey':
                $this->translationKey = null;
                $this->translation = '';
                Notification::make()->danger()->title('This key no longer exists. Choose another key.')->send();

                return;

            default:
                ResultNotifications::failure($result);
        }
    }

    /**
     * Puts the saved translation of the chosen key in the field. A language or key that has gone
     * away is dropped quietly: the selects then show nothing chosen.
     */
    private function loadTranslation(): void
    {
        $row = $this->currentRow();

        if ($row === null && $this->translationKey !== null) {
            $this->translationKey = null;
        }

        $this->translation = $row?->value ?? '';
    }

    private function currentRow(): ?TranslationRowDTO
    {
        if ($this->locale === null || $this->translationKey === null) {
            return null;
        }

        foreach ($this->rows() as $row) {
            if ($row->key === $this->translationKey) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return list<TranslationRowDTO>
     */
    private function rows(): array
    {
        if ($this->locale === null) {
            return [];
        }

        if ($this->rowsFor === $this->locale && $this->rows !== null) {
            return $this->rows;
        }

        $result = $this->service()->rows($this->locale, null, false);

        if ($result->isFailure()) {
            // The language was switched off since the page opened: pick another, as a save would ask.
            $this->locale = null;

            return [];
        }

        $this->rowsFor = $this->locale;

        return $this->rows = $result->getData();
    }

    /**
     * @return array<string, string> key to "key · English text", so a translator can also search by the English wording
     */
    private function keyOptions(): array
    {
        $options = [];

        foreach ($this->rows() as $row) {
            $options[$row->key] = "{$row->key} · {$row->source}";
        }

        return $options;
    }

    /**
     * @return array<string, string> code to "Name (code)"
     */
    private function localeOptions(): array
    {
        $options = [];

        foreach ($this->service()->editableLocales() as $locale) {
            $options[$locale->getCode()] = "{$locale->getName()} ({$locale->getCode()})";
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    private function editableCodes(): array
    {
        return array_map(
            static fn (TranslationLocale $locale): string => $locale->getCode(),
            $this->service()->editableLocales(),
        );
    }

    private function service(): TranslationEditorServiceInterface
    {
        return app(TranslationEditorServiceInterface::class);
    }
}
