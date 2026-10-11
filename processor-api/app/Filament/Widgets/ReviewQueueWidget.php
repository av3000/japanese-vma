<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Application\AdminDashboard\Interfaces\Readers\AdminDashboardReaderInterface;
use App\Domain\AdminDashboard\DTOs\ReviewQueueItemDTO;
use App\Domain\Shared\Enums\ArticleStatus;
use App\Filament\Support\PanelActor;
use Filament\Facades\Filament;
use Filament\Support\ArrayRecord;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ReviewQueueWidget extends TableWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return Filament::auth()->check() && PanelActor::may('articles.view');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Review queue')
            ->description('The '.AdminDashboardReaderInterface::REVIEW_QUEUE_SIZE.' oldest articles awaiting review.')
            ->records(static fn (): array => array_map(
                static fn (ReviewQueueItemDTO $item): array => [
                    ArrayRecord::getKeyName() => $item->uuid,
                    ...$item->toArray(),
                ],
                app(AdminDashboardReaderInterface::class)->reviewQueue(),
            ))
            ->paginated(false)
            ->emptyStateHeading('No articles are waiting for review')
            ->columns([
                TextColumn::make('title')->wrap(),
                TextColumn::make('author_name')->label('Author'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (int $state): string => ArticleStatus::from($state)->label()),
                TextColumn::make('created_at')
                    ->label('Submitted')
                    ->since()
                    ->dateTimeTooltip(),
            ]);
    }
}
