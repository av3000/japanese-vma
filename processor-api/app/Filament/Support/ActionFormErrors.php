<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Filament\Actions\Action;
use Illuminate\Validation\ValidationException;

/**
 * Puts a service's refusal under a field of an action's modal form, the way a page form shows it.
 *
 * A page form keeps its state at `data.<field>`. A modal form keeps it at
 * `mountedActions.<nesting index>.data.<field>`, so a ValidationException keyed `data.code` from
 * inside an action is shown nowhere. Use this from an action's `using()` or `action()` closure.
 */
final class ActionFormErrors
{
    public static function throw(Action $action, string $field, string $message): never
    {
        $index = $action->getNestingIndex() ?? 0;

        throw ValidationException::withMessages(["mountedActions.{$index}.data.{$field}" => $message]);
    }
}
