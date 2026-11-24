<?php

declare(strict_types=1);

namespace App\Filament\Pages\Translations;

use App\Application\Translations\Services\TranslationEditorServiceInterface;
use App\Application\Users\Support\PanelPermission;
use App\Domain\Translations\DTOs\TranslationCompletionDTO;
use App\Domain\Translations\DTOs\TranslationRowDTO;
use App\Domain\Translations\Enums\TranslationState;
use App\Domain\Translations\Models\TranslationLocale;
use App\Filament\AdminNavigationGroup;
use App\Filament\Support\PanelActor;
use App\Filament\Support\ResultNotifications;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Support\ArrayRecord;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use UnitEnum;

/**
 * Every English label for one language, with what is still untranslated and an exact completion
 * figure. A custom page on a custom data source, not an Eloquent table: a key with no stored row
 * has to be listed too. Opens on the Missing filter.
 *
 * Talks to `TranslationEditorServiceInterface` only, so what counts as translated is decided once,
 * in the resolver the service uses.
 */
class TranslationOverview extends Page implements HasTable
{
    use InteractsWithTable;

    private const FILTER_MISSING = 'missing';

    private const FILTER_ALL = 'all';

    private const PAGE_OPTIONS = [25, 50, 100];

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedListBullet;

    protected static string|UnitEnum|null $navigationGroup = AdminNavigationGroup::Localisation;

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Translations';

    protected static ?string $title = 'Translations';

    protected static ?string $slug = 'translations';

    /** The code of the language being worked on, one of the active non-English ones. */
    #[Url]
    public ?string $locale = null;

    public static function canAccess(): bool
    {
        return PanelActor::may(PanelPermission::TranslationsView);
    }

    /** @var list<TranslationLocale>|null The languages a translator can work on, read once per request. */
    private ?array $editableLocales = null;

    public function mount(): void
    {
        $this->syncLocale();
    }

    public function getSubheading(): ?string
    {
        $completion = $this->completion();

        if ($completion === null) {
            return null;
        }

        return "{$completion->translated} of {$completion->total} translated · {$completion->percent}% complete";
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('locale')
                ->label('Language')
                ->options($this->localeOptions())
                ->selectablePlaceholder(false)
                ->live()
                ->afterStateUpdated(fn () => $this->resetPage()),
            EmbeddedTable::make(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (?string $search, ?array $filters, int|string $page, int|string $recordsPerPage): LengthAwarePaginator => $this->records(
                $search,
                ($filters['state']['value'] ?? self::FILTER_MISSING) !== self::FILTER_ALL,
                (int) $page,
                (int) $recordsPerPage,
            ))
            ->paginationPageOptions(self::PAGE_OPTIONS)
            ->defaultPaginationPageOption(self::PAGE_OPTIONS[1])
            ->searchPlaceholder('Search key, English or translation')
            ->filters([
                SelectFilter::make('state')
                    ->label('Show')
                    ->options([
                        self::FILTER_MISSING => 'Missing',
                        self::FILTER_ALL => 'All',
                    ])
                    ->default(self::FILTER_MISSING)
                    ->selectablePlaceholder(false),
            ], layout: FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->emptyStateHeading(fn (): string => $this->locale === null ? 'No language to translate' : 'No keys match')
            ->emptyStateDescription(fn (): ?string => $this->locale === null ? 'Add and switch on a language first.' : null)
            ->columns([
                TextColumn::make('key')
                    ->label('Key')
                    ->fontFamily('mono')
                    ->searchable()
                    ->wrap()
                    ->url(fn (array $record): string => TranslationEditor::getUrl(['locale' => $this->locale, 'key' => $record['key']])),
                TextColumn::make('source')->label('English')->searchable()->wrap(),
                TextColumn::make('value')->label('Translation')->searchable()->wrap(),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(static fn (TranslationState $state): string => $state === TranslationState::Translated ? 'Translated' : 'Missing')
                    ->color(static fn (TranslationState $state): string => $state === TranslationState::Translated ? 'success' : 'gray')
                    ->icon(static fn (TranslationState $state): Heroicon => $state === TranslationState::Translated ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedMinusCircle),
            ]);
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function records(?string $search, bool $missingOnly, int $page, int $perPage): LengthAwarePaginator
    {
        $rows = $this->rows($search, $missingOnly);
        $page = max($page, 1);
        // `tableRecordsPerPage` is a public Livewire property, so a client can set it to anything,
        // and 0 would divide by zero in the paginator.
        $perPage = in_array($perPage, self::PAGE_OPTIONS, true) ? $perPage : self::PAGE_OPTIONS[1];

        return new LengthAwarePaginator(
            array_map(static fn (TranslationRowDTO $row): array => [
                ArrayRecord::getKeyName() => $row->key,
                'key' => $row->key,
                'source' => $row->source,
                'value' => $row->value,
                'state' => $row->state,
            ], array_slice($rows, ($page - 1) * $perPage, $perPage)),
            count($rows),
            $perPage,
            $page,
        );
    }

    /**
     * @return list<TranslationRowDTO>
     */
    private function rows(?string $search, bool $missingOnly): array
    {
        $locale = $this->syncLocale();

        if ($locale === null) {
            return [];
        }

        $result = $this->service()->rows($locale, $search, $missingOnly);

        if ($result->isFailure()) {
            ResultNotifications::failure($result);

            return [];
        }

        return $result->getData();
    }

    private function completion(): ?TranslationCompletionDTO
    {
        $locale = $this->syncLocale();

        if ($locale === null) {
            return null;
        }

        $result = $this->service()->completion($locale);

        return $result->isFailure() ? null : $result->getData();
    }

    /**
     * @return array<string, string> code to "Name (code)"
     */
    private function localeOptions(): array
    {
        $options = [];

        foreach ($this->editableLocales() as $locale) {
            $options[$locale->getCode()] = "{$locale->getName()} ({$locale->getCode()})";
        }

        return $options;
    }

    /**
     * Keeps `$locale` on a language a translator can work on: the requested one while it is active,
     * otherwise the first. It runs before every read, so a language switched off in another tab
     * never reaches the service as a failure.
     */
    private function syncLocale(): ?string
    {
        $codes = array_map(
            static fn (TranslationLocale $locale): string => $locale->getCode(),
            $this->editableLocales(),
        );

        return $this->locale = in_array($this->locale, $codes, true) ? $this->locale : ($codes[0] ?? null);
    }

    /**
     * @return list<TranslationLocale>
     */
    private function editableLocales(): array
    {
        return $this->editableLocales ??= $this->service()->editableLocales();
    }

    private function service(): TranslationEditorServiceInterface
    {
        return app(TranslationEditorServiceInterface::class);
    }
}
