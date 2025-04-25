<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Schema(
 * schema="AttendanceResource",
 * type="object",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="id_checkinout", type="string", example="ABC123XYZ"),
 * @OA\Property(property="nip_pegawai", type="string", example="199001012020121001"),
 * @OA\Property(property="id_instansi", type="integer", example=1),
 * @OA\Property(property="id_unit_kerja", type="integer", example=10),
 * @OA\Property(property="id_profile", type="integer", example=100),
 * @OA\Property(property="date", type="string", format="date", example="2025-04-25"),
 * @OA\Property(property="checktime", type="string", format="date-time", nullable=true, example="2025-04-25T08:00:00+08:00"),
 * @OA\Property(property="checktype", type="string", example="IN"),
 * @OA\Property(property="iplog", type="string", nullable=true, example="192.168.1.1"),
 * @OA\Property(property="coordinate", type="string", nullable=true, example="-6.175392,106.827153"),
 * @OA\Property(property="altitude", type="number", format="float", nullable=true, example=10.5),
 * @OA\Property(property="jenis_absensi", type="string", nullable=true, example="REGULER"),
 * @OA\Property(property="user_platform", type="string", nullable=true, example="Android"),
 * @OA\Property(property="browser_name", type="string", nullable=true, example="Android App"),
 * @OA\Property(property="browser_version", type="string", nullable=true, example="1.0.0"),
 * @OA\Property(property="aprv_stats", type="string", nullable=true, example="Y"),
 * @OA\Property(property="aprv_by", type="string", nullable=true, example="admin"),
 * @OA\Property(property="aprv_on", type="string", format="date-time", nullable=true, example="2025-04-25T08:15:00+08:00"),
 * @OA\Property(property="reject_by", type="string", nullable=true, example=null),
 * @OA\Property(property="reject_on", type="string", format="date-time", nullable=true, example=null),
 * @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2025-04-25T07:55:00+08:00"),
 * @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2025-04-25T08:00:00+08:00")
 * )
 */
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