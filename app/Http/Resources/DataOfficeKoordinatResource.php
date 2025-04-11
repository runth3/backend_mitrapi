<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DataOfficeKoordinatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        $koordinat = $this->koordinat && strpos($this->koordinat, ',') !== false
            ? array_map('floatval', explode(',', $this->koordinat))
            : [null, null];

        return [
            'id_ref_instansi_koordinat' => $this->id_ref_instansi_koordinat,
            'id_instansi' => $this->id_instansi,
            'koordinat' => [
                'latitude' => $koordinat[0],
                'longitude' => $koordinat[1],
            ],
            'block_koordinat' => $this->block_koordinat,
            'jarak_koordinat' => $this->jarak_koordinat,
            'wajib_absen' => $this->wajib_absen,
            'absen_shift' => $this->absen_shift,
            'created_at' => $this->cre_on?->toDateTimeString(),
            'created_by' => $this->cre_by,
            'updated_at' => $this->upd_on?->toDateTimeString(),
            'updated_by' => $this->upd_by,
            'aktif' => $this->aktif,
        ];
    }
}
