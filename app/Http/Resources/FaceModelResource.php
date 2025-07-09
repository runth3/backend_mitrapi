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

        try {
            // Return direct endpoint URL for private file access
            return url('/api/face-models/' . $this->id);
        } catch (\Exception $e) {
            \Log::error('Failed to generate image URL', [
                'face_model_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
}