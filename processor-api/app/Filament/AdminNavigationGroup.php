<?php

declare(strict_types=1);

namespace App\Filament;

use Filament\Support\Contracts\HasLabel;

/**
 * Panel navigation mirrors the product domains. Case order is sidebar order.
 */
enum AdminNavigationGroup: string implements HasLabel
{
    case Access = 'access';
    case Content = 'content';
    case Community = 'community';
    case StudyMaterial = 'study-material';
    case Operations = 'operations';

    public function getLabel(): string
    {
        return match ($this) {
            self::Access => 'Access',
            self::Content => 'Content',
            self::Community => 'Community',
            self::StudyMaterial => 'Study material',
            self::Operations => 'Operations',
        };
    }
}
