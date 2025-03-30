<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function latest()
    {
        $news = News::with('user')->latest()->take(5)->get();
        return response()->json($news);
    }

    // Create a new news item (Admin only)
    public function store(Request $request)
    {
        $user = $request->user();

        // Check if the user is an admin
        if (!$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', // Validate image
        ]);

        $imageUrl = null;

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('news_images', 'public');
            $imageUrl = asset('storage/' . $imagePath); // Generate public URL
        }

        $news = News::create([
            'title' => $request->title,
            'content' => $request->content,
            'image_url' => $imageUrl,
            'user_id' => $user->id, // Associate the news with the admin user
        ]);

        return response()->json($news, 201);
    }

    // Update an existing news item (Admin only)
    public function update(Request $request, $id)
    {
        $user = $request->user();

        // Check if the user is an admin
        if (!$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $news = News::findOrFail($id);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        $news->update($request->only(['title', 'content']));

        return response()->json($news);
    }

    // Delete a news item (Admin only)
    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        // Check if the user is an admin
        if (!$user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $news = News::findOrFail($id);
        $news->delete();

        return response()->json(['message' => 'News deleted successfully']);
    }

    // Show a specific news item
    public function show($id)
    {
        $news = News::with('user')->findOrFail($id);
        return response()->json($news);
    }
}