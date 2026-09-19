<?php

namespace App\Domain\Pdf\DTOs;

readonly class CatalogueSentencesPdfDTO implements CataloguePdfViewDataInterface
{
    /**
     * @param array<int, array<string, mixed>> $sentences
     */
    public function __construct(
        public string $frontendUrl,
        public CataloguePdfHeaderDTO $catalogue,
        public array $sentences,
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
            'sentences' => $this->sentences,
        ];
    }
}
