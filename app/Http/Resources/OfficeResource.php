<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class OfficeResource extends JsonResource
{
    protected $coordinates;
    protected $performanceConfig;

    public function __construct($resource, $coordinates = null, $performanceConfig = null)
    {
        parent::__construct($resource);
        $this->coordinates = $coordinates;
        $this->performanceConfig = $performanceConfig;
    }

    public function toArray($request)
    {
        $coordinates = $this->coordinates && $this->coordinates->koordinat
            ? explode(',', $this->coordinates->koordinat)
            : null;

        return [
            'id_instansi' => $this->id_instansi,
            'nama_instansi' => $this->nama_instansi,
            'alamat_instansi' => $this->alamat_instansi ?? null,
            'kota' => $this->kota ?? null,
            'coordinates' => $coordinates && count($coordinates) === 2 ? [
                'latitude' => (float) trim($coordinates[0]),
                'longitude' => (float) trim($coordinates[1]),
            ] : null,
            'performance_config' => $this->performanceConfig ?? null,
        ];
    }
}