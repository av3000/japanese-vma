<?php

declare(strict_types=1);

namespace App\Filament\Resources\Processing\Pages;

use App\Filament\Resources\Processing\Actions\RetryProcessingActions;
use App\Filament\Resources\Processing\ProcessingStateResource;
use Filament\Resources\Pages\ViewRecord;

class ViewProcessingState extends ViewRecord
{
    protected static string $resource = ProcessingStateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            RetryProcessingActions::retry(),
        ];
    }
}
