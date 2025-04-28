<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class PerformanceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'month' => now()->format('Y-m'),
            'performances' => PerformanceResource::collection($this->collection),
        ];
    }
}