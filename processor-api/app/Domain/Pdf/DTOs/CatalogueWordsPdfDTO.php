<?php

namespace App\Domain\Pdf\DTOs;

readonly class CatalogueWordsPdfDTO implements CataloguePdfViewDataInterface
{
    /**
     * @param array<int, array<string, mixed>> $words
     */
    public function __construct(
        public string $frontendUrl,
        public CataloguePdfHeaderDTO $catalogue,
        public array $words,
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
            'words' => $this->words,
        ];
    }
}
