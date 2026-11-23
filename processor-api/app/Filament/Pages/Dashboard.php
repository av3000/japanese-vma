<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Application\AdminDashboard\Interfaces\Caches\AdminDashboardCacheInterface;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh figures')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->action(function (): void {
                    app(AdminDashboardCacheInterface::class)->forget();

                    $this->redirect(static::getUrl());
                }),
        ];
    }
}
