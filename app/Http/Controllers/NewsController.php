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
}