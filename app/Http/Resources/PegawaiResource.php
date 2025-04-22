<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PegawaiResource extends JsonResource
{
    protected $officeRelation;

    /**
     * Constructor to accept resource and office relation.
     *
     * @param mixed $resource
     * @param string $officeRelation
     */
    public function __construct($resource, $officeRelation)
    {
        parent::__construct($resource);
        $this->officeRelation = $officeRelation;
    }

    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = parent::toArray($request);
        $office = $this->resource->{$this->officeRelation};

        $data['office'] = $office ? [
            'id_instansi' => $office->id_instansi ?? $office->id ?? null,
            'nama_instansi' => $office->nama_instansi ?? $office->nama ?? null,
        ] : null;

        return $data;
    }
}