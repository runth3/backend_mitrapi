<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function index()
    {
        $news = News::with('user')->latest()->paginate(10);
        return response()->json($news);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'image_url' => 'nullable|url',
        ]);

        $news = News::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json($news, 201);
    }

    public function show(News $news)
    {
        return response()->json($news->load('user'));
    }

    public function update(Request $request, News $news)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'image_url' => 'nullable|url',
        ]);

        $news->update($validated);
        return response()->json($news);
    }

    public function destroy(News $news)
    {
        $news->delete();
        return response()->json(null, 204);
    }
}