<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id_instansi' => $this->id_instansi ?? $this->id ?? null,
            'nama_instansi' => $this->nama_instansi ?? $this->nama ?? null,
            'alamat_instansi' => $this->alamat_instansi ?? null,
            'kota' => $this->kota ?? null,
            'kodepos' => $this->kodepos ?? null,
            'phone' => $this->phone ?? null,
            'fax' => $this->fax ?? null,
            'website' => $this->website ?? null,
            'email' => $this->email ?? null,
        ];
    }
}