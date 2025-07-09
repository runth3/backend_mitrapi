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

            Log::info('Face models retrieved', [
                'user_id' => $user->id,
                'count' => $faceModels->count(),
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new FaceModelCollection($faceModels),
                message: 'Face models retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve face models', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
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
                    Log::warning('Unauthorized attempt to upload face model for another user', [
                        'user_id' => $authenticatedUser->id,
                        'target_user_id' => $targetUserId,
                        'ip' => $request->ip(),
                        'headers' => $request->headers->all(),
                    ]);
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
            $filePath = $request->file('image')->storeAs($folderPath, $fileName, 'local');

            // Auto-activate if user uploads their own face model, otherwise keep inactive
            $autoActivate = $targetUser->id === $authenticatedUser->id;
            
            if ($autoActivate) {
                // Deactivate all existing face models for this user
                FaceModel::where('user_id', $targetUser->id)->update(['is_active' => false]);
            }
            
            $faceModel = FaceModel::create([
                'user_id' => $targetUser->id,
                'image_path' => $filePath,
                'is_active' => $autoActivate,
            ]);

            Log::info('Face model created', [
                'user_id' => $targetUser->id,
                'face_model_id' => $faceModel->id,
                'file_path' => $filePath,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new FaceModelResource($faceModel),
                message: 'Face model created successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info('Face model creation failed: Validation error', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'errors' => $e->errors(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input',
                statusCode: 400,
                details: $e->errors()
            );
        } catch (\Exception $e) {
            Log::error('Face model creation failed', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
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
                Log::warning('Face model not found', [
                    'user_id' => $user->id,
                    'face_model_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Face model not found',
                    statusCode: 404,
                    details: null
                );
            }

            if ($faceModel->user_id !== $user->id && !$user->is_admin) {
                Log::warning('Unauthorized attempt to set active face model', [
                    'user_id' => $user->id,
                    'face_model_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized: You cannot modify this face model',
                    statusCode: 403,
                    details: null
                );
            }

            FaceModel::where('user_id', $faceModel->user_id)->update(['is_active' => false]);
            $faceModel->update(['is_active' => true]);

            Log::info('Face model set as active', [
                'user_id' => $faceModel->user_id,
                'face_model_id' => $id,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new FaceModelResource($faceModel),
                message: 'Face model set as active',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to set active face model', [
                'user_id' => $request->user()->id ?? 'unknown',
                'face_model_id' => $id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
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
                Log::info('No active face model found', [
                    'user_id' => $userId,
                    'requester_id' => $user->id,
                ]);
                return $this->errorResponse(
                    message: 'No active face model found',
                    statusCode: 404,
                    details: null
                );
            }

            Log::info('Active face model retrieved', [
                'user_id' => $userId,
                'face_model_id' => $faceModel->id,
                'requester_id' => $user->id,
            ]);

            return $this->successResponse(
                data: new FaceModelResource($faceModel),
                message: 'Active face model retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve active face model', [
                'user_id' => $request->query('user_id', $request->user()->id ?? 'unknown'),
                'error' => $e->getMessage(),
            ]);
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
                Log::warning('Face model not found or unauthorized', [
                    'user_id' => $user->id,
                    'face_model_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Face model not found or you are not authorized',
                    statusCode: 404,
                    details: null
                );
            }

            Storage::disk('local')->delete($faceModel->image_path);
            $faceModel->delete();

            Log::info('Face model deleted', [
                'user_id' => $user->id,
                'face_model_id' => $id,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: null,
                message: 'Face model deleted successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to delete face model', [
                'user_id' => $request->user()->id ?? 'unknown',
                'face_model_id' => $id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
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
                Log::warning('Face model not found', [
                    'user_id' => $user->id,
                    'face_model_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Face model not found',
                    statusCode: 404,
                    details: null
                );
            }

            if ($faceModel->user_id !== $user->id && !$user->is_admin) {
                Log::warning('Unauthorized attempt to view face model', [
                    'user_id' => $user->id,
                    'face_model_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized: You cannot view this face model',
                    statusCode: 403,
                    details: null
                );
            }

            $filePath = storage_path('app/' . $faceModel->image_path);

            Log::info('Checking face model file', [
                'user_id' => $user->id,
                'face_model_id' => $id,
                'image_path' => $faceModel->image_path,
                'full_file_path' => $filePath,
                'file_exists' => file_exists($filePath),
            ]);

            if (!file_exists($filePath)) {
                Log::warning('Face model file not found', [
                    'user_id' => $user->id,
                    'face_model_id' => $id,
                    'file_path' => $filePath,
                    'image_path' => $faceModel->image_path,
                    'ip' => $request->ip(),
                ]);
                return $this->errorResponse(
                    message: 'Face model file not found',
                    statusCode: 404,
                    details: ['file_path' => $filePath, 'image_path' => $faceModel->image_path]
                );
            }

            Log::info('Face model retrieved', [
                'user_id' => $user->id,
                'face_model_id' => $id,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return response()->file($filePath);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve face model', [
                'user_id' => $request->user()->id ?? 'unknown',
                'face_model_id' => $id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
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
                Log::warning('Unauthorized attempt to view face models for another user', [
                    'user_id' => $user->id,
                    'target_user_id' => $userId,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized: You cannot view face models for other users',
                    statusCode: 403,
                    details: null
                );
            }

            $faceModels = FaceModel::where('user_id', $userId)->get();

            if ($faceModels->isEmpty()) {
                Log::info('No face models found for user', [
                    'user_id' => $user->id,
                    'target_user_id' => $userId,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'No face models found for this user',
                    statusCode: 404,
                    details: null
                );
            }

            Log::info('Face models retrieved for user', [
                'user_id' => $user->id,
                'target_user_id' => $userId,
                'count' => $faceModels->count(),
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new FaceModelCollection($faceModels),
                message: 'Face models retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve face models for user', [
                'user_id' => $request->user()->id ?? 'unknown',
                'target_user_id' => $userId,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to retrieve face models',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }
}