<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

class AttendanceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        // Log jika ada field tanggal yang null
        $nullFields = [];
        if (is_null($this->checktime)) $nullFields[] = 'checktime';
        if (is_null($this->aprv_on)) $nullFields[] = 'aprv_on';
        if (is_null($this->reject_on)) $nullFields[] = 'reject_on';
        if (is_null($this->created_at)) $nullFields[] = 'created_at';
        if (is_null($this->updated_at)) $nullFields[] = 'updated_at';

        if (!empty($nullFields)) {
            Log::warning('Null timestamp fields detected in AttendanceResource', [
                'attendance_id' => $this->id,
                'nip_pegawai' => $this->nip_pegawai,
                'null_fields' => $nullFields,
            ]);
        }

        return [
            'id' => $this->id,
            'id_checkinout' => $this->id_checkinout,
            'nip_pegawai' => $this->nip_pegawai,
            'id_instansi' => $this->id_instansi,
            'id_unit_kerja' => $this->id_unit_kerja,
            'id_profile' => $this->id_profile,
            'date' => $this->date,
            'checktime' => $this->checktime ? Carbon::parse($this->checktime)->toIso8601String() : null,
            'checktype' => $this->checktype,
            'iplog' => $this->iplog,
            'coordinate' => $this->coordinate,
            'altitude' => $this->altitude,
            'jenis_absensi' => $this->jenis_absensi,
            'user_platform' => $this->user_platform,
            'browser_name' => $this->browser_name,
            'browser_version' => $this->browser_version,
            'aprv_stats' => $this->aprv_stats,
            'aprv_by' => $this->aprv_by,
            'aprv_on' => $this->aprv_on ? Carbon::parse($this->aprv_on)->toIso8601String() : null,
            'reject_by' => $this->reject_by,
            'reject_on' => $this->reject_on ? Carbon::parse($this->reject_on)->toIso8601String() : null,
            'created_at' => $this->created_at ? Carbon::parse($this->created_at)->toIso8601String() : null,
            'updated_at' => $this->updated_at ? Carbon::parse($this->updated_at)->toIso8601String() : null,
        ];
    }
}