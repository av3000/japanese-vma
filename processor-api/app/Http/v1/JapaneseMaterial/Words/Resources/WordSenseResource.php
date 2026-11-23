<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Words\Resources;

use App\Domain\JapaneseMaterial\Words\ValueObjects\WordSense;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @property WordSense $resource */
class WordSenseResource extends JsonResource
{
    public static $wrap = null;

    /**
     * @return array{
     *     parts_of_speech: array<int, string>,
     *     glosses: array<int, string>,
     *     notes: array<int, string>,
     *     misc: array<int, string>
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            /** @var array<int, string> */
            'parts_of_speech' => $this->resource->partsOfSpeech,
            /** @var array<int, string> */
            'glosses' => $this->resource->glosses,
            /** @var array<int, string> */
            'notes' => $this->resource->notes,
            /** @var array<int, string> */
            'misc' => $this->resource->misc,
        ];
    }
}
