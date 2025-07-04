<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationLetterResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id_data_permohonan,
            'nip_pegawai' => $this->nip_pegawai,
            'jenis_permohonan' => $this->jenis_permohonan,
            'tgl_mulai' => $this->tgl_mulai?->format('Y-m-d'),
            'tgl_selesai' => $this->tgl_selesai?->format('Y-m-d'),
            'deskripsi' => $this->deskripsi,
            'alasan' => $this->alasan,
            'status' => $this->status,
            'status_text' => $this->getStatusText(),
            'created_by' => $this->cre_by,
            'created_on' => $this->cre_on?->format('Y-m-d H:i:s'),
            'approved_by' => $this->aprv_by,
            'approved_on' => $this->aprv_on?->format('Y-m-d H:i:s'),
            'rejected_by' => $this->reject_by,
            'rejected_on' => $this->reject_on?->format('Y-m-d H:i:s')
        ];
    }

    private function getStatusText()
    {
        return match($this->status) {
            1 => 'Pending',
            2 => 'Approved',
            3 => 'Rejected',
            default => 'Unknown'
        };
    }
}