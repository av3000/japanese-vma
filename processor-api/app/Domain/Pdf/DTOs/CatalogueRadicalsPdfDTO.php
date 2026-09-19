<?php

namespace App\Domain\Pdf\DTOs;

readonly class CatalogueRadicalsPdfDTO implements CataloguePdfViewDataInterface
{
    /**
     * @param array<int, array<string, mixed>> $radicals
     */
    public function __construct(
        public string $frontendUrl,
        public CataloguePdfHeaderDTO $catalogue,
        public array $radicals,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'frontend_url' => $this->frontendUrl,
            'catalogue' => $this->catalogue->toViewData(),
            'radicals' => $this->radicals,
        ];
    }
}
