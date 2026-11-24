<?php

declare(strict_types=1);

namespace App\Mcp\Presenters;

use App\Domain\Catalogues\DTOs\CatalogueListItemDTO;
use App\Mcp\Support\DeckKind;

/**
 * Shapes a study list of a search page for an assistant: enough to pick one to drill, and none of
 * the integer ids, owner details, engagement counts or publicity the website's own resources carry.
 */
final class DeckPresenter
{
    /**
     * @return array{uuid: string, title: string, description: ?string, kind: string, items_count: int}
     */
    public function present(CatalogueListItemDTO $item): array
    {
        $catalogue = $item->catalogue;

        return [
            'uuid' => $catalogue->getUid()->value(),
            'title' => (string) $catalogue->getTitle(),
            'description' => $catalogue->getDescription()->value,
            // The tool only asks for the three kinds, so a list of any other type never reaches here.
            'kind' => DeckKind::fromListType($catalogue->getType())?->value ?? $catalogue->getTypeLabel(),
            'items_count' => $item->itemsCount,
        ];
    }
}
