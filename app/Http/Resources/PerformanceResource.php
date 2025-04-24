<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

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
        // Log jika created_at atau updated_at null
        $nullFields = [];
        if (is_null($this->created_at)) $nullFields[] = 'created_at';
        if (is_null($this->updated_at)) $nullFields[] = 'updated_at';

        if (!empty($nullFields)) {
            Log::warning('Null timestamp fields detected in PerformanceResource', [
                'performance_id' => $this->id,
                'NIP' => $this->NIP,
                'null_fields' => $nullFields,
            ]);
        }

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
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toIso8601String() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toIso8601String() : null,
        ];
    }
}