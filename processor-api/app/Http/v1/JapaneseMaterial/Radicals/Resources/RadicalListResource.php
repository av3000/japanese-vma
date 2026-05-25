<?php

declare(strict_types=1);

namespace App\Http\v1\JapaneseMaterial\Radicals\Resources;

use App\Domain\JapaneseMaterial\Radicals\DTOs\RadicalListResultDTO;
use App\Domain\JapaneseMaterial\Radicals\Models\Radical;
use App\Http\v1\Shared\Resources\PaginationResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RadicalListResource extends JsonResource
{
    public static $wrap = null;

    public function __construct(RadicalListResultDTO $resource)
    {
        parent::__construct($resource);
    }

    /**
     * @return array{
     *     items: array<int, RadicalResource>,
     *     pagination: PaginationResource
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var RadicalListResultDTO $result */
        $result = $this->resource;

        return [
            'items' => array_map(
                fn (Radical $radical): RadicalResource => new RadicalResource($radical),
                $result->items,
            ),
            'pagination' => new PaginationResource($result->pagination),
        ];
    }
}
