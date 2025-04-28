<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class AttendanceCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'month' => now()->format('Y-m'),
            'attendances' => AttendanceResource::collection($this->collection),
        ];
    }
}