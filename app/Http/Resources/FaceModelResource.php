<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class FaceModelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'image_url' => $this->getImageUrl(), // Signed URL for private file
            'is_active' => $this->is_active,
            'user' => [
                'id' => $this->user->id,
                'username' => $this->user->username,
            ],
        ];
    }

    /**
     * Generate a signed URL for the image.
     *
     * @return string|null
     */
    protected function getImageUrl()
    {
        if (!$this->image_path) {
            return null;
        }

        // Generate a temporary signed URL for the private file
        return Storage::disk('local')->temporaryUrl(
            $this->image_path,
            now()->addMinutes(60) // URL valid for 60 minutes
        );
    }
}