<?php

namespace App\Http\Controllers;

use App\Models\FaceModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; 
use Illuminate\Support\Facades\Log;
class FaceModelController extends Controller
{
    /**
     * Get all face models for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $faceModels = FaceModel::where('user_id', $user->id)->get();

        return response()->json($faceModels);
    }

    /**
     * Store a new face model.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validate image file
            'user_id' => 'nullable|exists:users,id', // Validate user_id if present
        ]);

        $authenticatedUser = $request->user(); // Get the authenticated user
        $targetUserId = $request->user_id;

        // Determine the target user and folder path
        if ($targetUserId) {
            // Admin is uploading for a specific user
            if (!$authenticatedUser->is_admin) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }
            $targetUser = User::findOrFail($targetUserId);
        } else {
            // Regular user is uploading for themselves
            $targetUser = $authenticatedUser;
        }

        // Generate a unique folder path based on the target user's username and timestamp
        $folderPath = 'faces/' . $targetUser->username;
        $fileName = time() . '.' . $request->file('image')->getClientOriginalExtension();

        // Store the image in the respective folder using the local (private) disk
        $filePath = $request->file('image')->storeAs($folderPath, $fileName, 'local');

        // Create a new face model record
        $faceModel = FaceModel::create([
            'user_id' => $targetUser->id, // Use the target user's ID
            'image_path' => $filePath, // Save the relative path to the database
            'is_active' => false, // Default to inactive
        ]);

        return response()->json($faceModel, 201);
    }

    /**
     * Set a specific face model as active.
     */
    public function setActive(Request $request, $id)
    { 
     
        $user = $request->user();
        $faceModel = FaceModel::findOrFail($id);
        // check faceModel is available
        if (!$faceModel) {
            return response()->json(['message' => 'Face model not found'], 404);
        }
        // set faceModel is_active to false using faceModel->user_id
        FaceModel::where('user_id', $faceModel->user_id)->update(['is_active' => false]);
      
        // Activate the selected face model
        FaceModel::where('id', $id)->update(['is_active' => true]); 
        Log::info('Sort Parameters:', [
            'id' => $id, 
            'user_id' => $faceModel->user_id
        ]);
        return response()->json(['message' => 'Face model set as active', 'face_model' => $faceModel]);
    }

    /**
     * Get the active face model for the authenticated user.
     */
    public function getActive(Request $request)
    {
        $user = $request->user();

        // Retrieve the latest active face model for the user
        $faceModel = FaceModel::where('user_id', $user->id)
            ->where('is_active', true)
            ->latest('updated_at') // Sort by the most recently updated active model
            ->first();

        if (!$faceModel) {
            return response()->json(['message' => 'No active face model found'], 404);
        }

        return response()->json($faceModel);
    }

    /**
     * Delete a face model.
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $faceModel = FaceModel::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $faceModel->delete();

        return response()->json(['message' => 'Face model deleted successfully']);
    }

    /**
     * Show a specific face model.
     */
    public function show(Request $request, $id)
    {
        $user = $request->user();

        // Find the face model
        $faceModel = FaceModel::findOrFail($id);

        // Check if the user is the owner or an admin
        if ($faceModel->user_id !== $user->id && !$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Serve the file securely
        $filePath = storage_path('app/private/' . $faceModel->image_path);

        if (!file_exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return response()->file($filePath);
    }
    /**
     * Get all face models for a specific user by user_id.
     */
    public function getByUserId(Request $request, $userId)
    {
        $faceModels = FaceModel::where('user_id', $userId)->get();

        // Check if any face models were found
        if ($faceModels->isEmpty()) {
            return response()->json(['message' => 'No face models found for this user'], 404);
        }

        return response()->json($faceModels);
    }

}
