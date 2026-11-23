<?php

namespace App\Domain\Pdf\DTOs;

readonly class CatalogueKanjisPdfDTO implements CataloguePdfViewDataInterface
{
    /**
     * @param array<int, array<string, mixed>> $kanjis
     */
    public function __construct(
        public string $frontendUrl,
        public CataloguePdfHeaderDTO $catalogue,
        public array $kanjis,
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
            'kanjis' => $this->kanjis,
        ];
    }
}
