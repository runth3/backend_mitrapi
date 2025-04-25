<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserkinerjaResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request); // Sesuaikan jika perlu field spesifik
    }
}