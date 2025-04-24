<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PerformanceResource extends JsonResource
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
            'id' => $this->id,
            'nama' => $this->nama,
            'penjelasan' => $this->penjelasan,
            'tglKinerja' => $this->tglKinerja,
            'durasiKinerjaMulai' => $this->durasiKinerjaMulai,
            'durasiKinerjaSelesai' => $this->durasiKinerjaSelesai,
            'durasiKinerja' => $this->durasiKinerja,
            'menitKinerja' => $this->menitKinerja,
            'apv' => $this->apv,
            'tupoksi' => $this->tupoksi,
            'periodeKinerja' => $this->periodeKinerja,
            'target' => $this->target,
            'satuanTarget' => $this->satuanTarget,
            'NIP' => $this->NIP,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}