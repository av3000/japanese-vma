<?php

namespace App\Domain\Pdf\Enums;

enum PdfExportSource: string
{
    case ARTICLE = 'article';
    case CATALOGUE = 'catalogue';
}
