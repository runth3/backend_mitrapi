<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DataOfficeEkinerjaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'jam_kerja' => $this->jam_kerja,
            'menit_kerja' => $this->menit_kerja,
            'menit_kerja_harian' => $this->menit_kerja_harian,
        ];
    }
}