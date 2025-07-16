<?php

namespace App\Http\Controllers;

use App\Http\Resources\FaceModelResource;
use App\Http\Resources\FaceModelCollection;
use App\Models\FaceModel;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class FaceModelController extends Controller
{
    use ApiResponseTrait;

    /**
     * Get all face models for the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $faceModels = FaceModel::where('user_id', $user->id)->get();



            return $this->successResponse(
                data: new FaceModelCollection($faceModels),
                message: 'Face models retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                message: 'Failed to retrieve face models',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Store a new face model.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg|max:2048',
                'user_id' => 'nullable|exists:users,id',
            ]);

            $authenticatedUser = $request->user();
            $targetUserId = $request->input('user_id');

            if ($targetUserId) {
                if (!$authenticatedUser->is_admin) {

                    return $this->errorResponse(
                        message: 'Unauthorized: Only admins can upload for other users',
                        statusCode: 403,
                        details: null
                    );
                }
                $targetUser = User::findOrFail($targetUserId);
            } else {
                $targetUser = $authenticatedUser;
            }

            $folderPath = 'faces/' . $targetUser->username;
            $fileName = time() . '.' . $request->file('image')->getClientOriginalExtension();
            $filePath = $request->file('image')->storeAs($folderPath, $fileName, 'public');
            $publicUrl = asset('storage/' . $filePath);

            // Auto-activate if user uploads their own face model, otherwise keep inactive
            $autoActivate = $targetUser->id === $authenticatedUser->id;
            
            if ($autoActivate) {
                // Deactivate all existing face models for this user
                FaceModel::where('user_id', $targetUser->id)->update(['is_active' => false]);
            }
            
            $faceModel = FaceModel::create([
                'user_id' => $targetUser->id,
                'image_path' => $publicUrl,
                'is_active' => $autoActivate,
            ]);



            return $this->successResponse(
                data: new FaceModelResource($faceModel),
                message: 'Face model created successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {

            return $this->errorResponse(
                message: 'Invalid input',
                statusCode: 400,
                details: $e->errors()
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                message: 'Failed to create face model',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Set a specific face model as active.
     */
    public function setActive(Request $request, $id)
    {
        try {
            $user = $request->user();
            $faceModel = FaceModel::find($id);

            if (!$faceModel) {

                return $this->errorResponse(
                    message: 'Face model not found',
                    statusCode: 404,
                    details: null
                );
            }

            if ($faceModel->user_id !== $user->id && !$user->is_admin) {

                return $this->errorResponse(
                    message: 'Unauthorized: You cannot modify this face model',
                    statusCode: 403,
                    details: null
                );
            }

            FaceModel::where('user_id', $faceModel->user_id)->update(['is_active' => false]);
            $faceModel->update(['is_active' => true]);



            return $this->successResponse(
                data: new FaceModelResource($faceModel),
                message: 'Face model set as active',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                message: 'Failed to set active face model',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get the active face model for the authenticated user.
     */
    public function getActive(Request $request)
    {
        try {
            $user = $request->user();
            $userId = $request->query('user_id', $user->id);
            
            // Check permission if requesting another user's model
            if ($userId != $user->id && !$user->is_admin) {
                return $this->errorResponse(
                    message: 'Unauthorized: Cannot access other users\' face models',
                    statusCode: 403,
                    details: null
                );
            }
            
            $faceModel = FaceModel::where('user_id', $userId)
                ->where('is_active', true)
                ->latest('updated_at')
                ->first();

            if (!$faceModel) {

                return $this->errorResponse(
                    message: 'No active face model found',
                    statusCode: 404,
                    details: null
                );
            }



            return $this->successResponse(
                data: new FaceModelResource($faceModel),
                message: 'Active face model retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                message: 'Failed to retrieve active face model',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Delete a face model.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = $request->user();
            $faceModel = FaceModel::where('id', $id)->where('user_id', $user->id)->first();

            if (!$faceModel) {

                return $this->errorResponse(
                    message: 'Face model not found or you are not authorized',
                    statusCode: 404,
                    details: null
                );
            }

            Storage::disk('public')->delete(str_replace(asset('storage/'), '', $faceModel->image_path));
            $faceModel->delete();



            return $this->successResponse(
                data: null,
                message: 'Face model deleted successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                message: 'Failed to delete face model',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Show a specific face model.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = $request->user();
            $faceModel = FaceModel::find($id);
            


            if (!$faceModel) {
                return $this->errorResponse(
                    message: 'Face model not found',
                    statusCode: 404,
                    details: null
                );
            }

            if ($faceModel->user_id !== $user->id && !$user->is_admin) {

                return $this->errorResponse(
                    message: 'Unauthorized: You cannot view this face model',
                    statusCode: 403,
                    details: null
                );
            }

            if (!$faceModel->image_path) {
                return $this->errorResponse(
                    message: 'Face model image not available',
                    statusCode: 404,
                    details: null
                );
            }

            // Handle old data that may only have filename
            $imagePath = $faceModel->image_path;
            if (!str_starts_with($imagePath, 'http')) {
                $imagePath = asset('storage/' . $imagePath);
            }

            return $this->successResponse(
                data: ['image_path' => $imagePath],
                message: 'Face model image path retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                message: 'Failed to retrieve face model',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get all face models for a specific user by user_id.
     */
    public function getByUserId(Request $request, $userId)
    {
        try {
            $user = $request->user();

            if ($userId != $user->id && !$user->is_admin) {

                return $this->errorResponse(
                    message: 'Unauthorized: You cannot view face models for other users',
                    statusCode: 403,
                    details: null
                );
            }

            $faceModels = FaceModel::where('user_id', $userId)->get();

            if ($faceModels->isEmpty()) {

                return $this->errorResponse(
                    message: 'No face models found for this user',
                    statusCode: 404,
                    details: null
                );
            }



            return $this->successResponse(
                data: new FaceModelCollection($faceModels),
                message: 'Face models retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {

            return $this->errorResponse(
                message: 'Failed to retrieve face models',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }
}