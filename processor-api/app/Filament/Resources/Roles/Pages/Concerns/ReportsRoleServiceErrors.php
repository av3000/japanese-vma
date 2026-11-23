<?php

declare(strict_types=1);

namespace App\Filament\Resources\Roles\Pages\Concerns;

use App\Shared\Results\ResultError;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * Turns a RoleService failure into panel feedback. Permission errors have no single field to sit
 * under - the catalogue renders one checkbox list per group - so they become a notification.
 */
trait ReportsRoleServiceErrors
{
    protected function reportRoleServiceError(ResultError $error): void
    {
        $message = $error->errorMessage ?? $error->description;

        if ($error->code === 'Roles.InvalidPermissions') {
            Notification::make()
                ->danger()
                ->title($message)
                ->send();

            $this->halt();
        }

        throw ValidationException::withMessages([
            'data.name' => $message,
        ]);
    }
}
