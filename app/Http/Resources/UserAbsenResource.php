<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserAbsenResource extends JsonResource
{
    public function toArray($request)
    {
        return parent::toArray($request); // Sesuaikan jika perlu field spesifik
    }
}