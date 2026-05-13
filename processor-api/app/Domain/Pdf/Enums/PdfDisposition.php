<?php

namespace App\Domain\Pdf\Enums;

enum PdfDisposition: string
{
    case INLINE = 'inline';
    case ATTACHMENT = 'attachment';
}
