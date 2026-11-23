<?php

declare(strict_types=1);

namespace App\Domain\Study\Enums;

enum AnswerMode: string
{
    /** Four options, one correct. */
    case OPTIONS = 'options';

    /** The learner types the answer; graded by the client. */
    case TYPED = 'typed';
}
