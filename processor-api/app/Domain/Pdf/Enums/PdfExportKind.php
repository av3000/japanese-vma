<?php

namespace App\Domain\Pdf\Enums;

enum PdfExportKind: string
{
    case KANJIS = 'kanjis';
    case WORDS = 'words';
    case RADICALS = 'radicals';
    case SENTENCES = 'sentences';

    /**
     * The kind is the value that crosses the wire and the download ledger, so it owns the
     * identity of the export: which Blade renders it and what the file is called.
     */
    public function view(): string
    {
        return 'pdf.catalogues.'.$this->value;
    }

    public function filename(): string
    {
        return 'catalogue-'.$this->value.'.pdf';
    }
}
