<?php

namespace App\Http\Controllers;

use App\Models\FaceModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
     * Store a new face model for the authenticated user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048', // Validate image file
        ]);

        $user = $request->user();

        // Generate a unique folder path based on the username and timestamp
        $folderPath = 'faces/' . $user->username;
        $fileName = time() . '.' . $request->file('image')->getClientOriginalExtension();

        // Store the image in the respective folder using the local (private) disk
        $filePath = $request->file('image')->storeAs($folderPath, $fileName, 'local');

        // Create a new face model record
        $faceModel = FaceModel::create([
            'user_id' => $user->id,
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

        // Deactivate all other face models for the user
        FaceModel::where('user_id', $user->id)->update(['is_active' => false]);

        // Activate the selected face model
        $faceModel = FaceModel::where('id', $id)->where('user_id', $user->id)->firstOrFail();
        $faceModel->is_active = true;
        $faceModel->save();

        return response()->json(['message' => 'Face model set as active', 'face_model' => $faceModel]);
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
}
