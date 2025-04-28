<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PegawaiResource extends JsonResource
{ 

    /**
     * Constructor to accept resource and office relation.
     *
     * @param mixed $resource
     * @param string $officeRelation
     */
    public function __construct($resource)
    {
        parent::__construct($resource); 
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
        return $data;
    }
}