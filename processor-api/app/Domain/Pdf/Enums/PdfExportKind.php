<?php

namespace App\Domain\Pdf\Enums;

enum PdfExportKind: string
{
    case KANJIS = 'kanjis';
    case WORDS = 'words';
}
