<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

/**
 * "From / until" filter on a timestamp column, inclusive of both days, in the app timezone.
 */
final class DateRangeFilter
{
    public static function make(string $column = 'created_at', string $label = 'Created'): Filter
    {
        return Filter::make("{$column}_range")
            ->label($label)
            ->schema([
                DatePicker::make('from')->label("{$label} from"),
                DatePicker::make('until')->label("{$label} until"),
            ])
            ->query(static function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when($data['from'] ?? null, static fn (Builder $q, string $from): Builder => $q->whereDate($column, '>=', $from))
                    ->when($data['until'] ?? null, static fn (Builder $q, string $until): Builder => $q->whereDate($column, '<=', $until));
            })
            ->indicateUsing(static function (array $data) use ($label): array {
                $indicators = [];

                if ($data['from'] ?? null) {
                    $indicators[] = Indicator::make("{$label} from {$data['from']}")->removeField('from');
                }

                if ($data['until'] ?? null) {
                    $indicators[] = Indicator::make("{$label} until {$data['until']}")->removeField('until');
                }

                return $indicators;
            });
    }
}
