<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class NewsController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a paginated list of news.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 20);
            $news = News::orderBy('created_at', 'desc')->paginate($perPage);

            Log::info('News list retrieved', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'per_page' => $perPage,
            ]);

            return $this->successResponse(
                data: NewsResource::collection($news),
                message: 'News list retrieved successfully',
                meta: [
                    'current_page' => $news->currentPage(),
                    'per_page' => $news->perPage(),
                    'total' => $news->total(),
                    'last_page' => $news->lastPage(),
                    'from' => $news->firstItem(),
                    'to' => $news->lastItem(),
                ],
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve news list', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Display the latest 5 news items.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function latest(Request $request)
    {
        try {
            $news = News::with('user')->latest()->take(5)->get();

            Log::info('Latest news retrieved', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $this->successResponse(
                data: NewsResource::collection($news),
                message: 'Latest news retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve latest news', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Create a new news item (Admin only).
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                Log::info('News creation failed: Validation error', [
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('news_images', 'public');
                $imageUrl = asset('storage/' . $imagePath);
            }

            $news = News::create([
                'title' => $request->title,
                'content' => $request->content,
                'image_url' => $imageUrl,
                'user_id' => $request->user()->id,
            ]);

            Log::info('News created successfully', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'news_id' => $news->id,
            ]);

            return $this->successResponse(
                data: new NewsResource($news),
                message: 'News created successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create news', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Update an existing news item (Admin only).
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            $news = News::find($id);
            if (!$news) {
                Log::info('News update failed: News not found', [
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'news_id' => $id,
                ]);
                return $this->errorResponse('News not found', 404);
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                Log::info('News update failed: Validation error', [
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'news_id' => $id,
                    'errors' => $validator->errors()->toArray(),
                ]);
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $data = $request->only(['title', 'content']);
            if ($request->hasFile('image')) {
                // Delete old image if exists
                if ($news->image_url) {
                    $oldImagePath = str_replace(asset('storage/'), 'public/', $news->image_url);
                    Storage::delete($oldImagePath);
                }
                $imagePath = $request->file('image')->store('news_images', 'public');
                $data['image_url'] = asset('storage/' . $imagePath);
            }

            $news->update($data);

            Log::info('News updated successfully', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'news_id' => $news->id,
            ]);

            return $this->successResponse(
                data: new NewsResource($news),
                message: 'News updated successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to update news', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'news_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Delete a news item (Admin only).
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id)
    {
        try {
            $news = News::find($id);
            if (!$news) {
                Log::info('News deletion failed: News not found', [
                    'user_id' => $request->user()->id,
                    'ip' => $request->ip(),
                    'news_id' => $id,
                ]);
                return $this->errorResponse('News not found', 404);
            }

            // Delete image if exists
            if ($news->image_url) {
                $imagePath = str_replace(asset('storage/'), 'public/', $news->image_url);
                Storage::delete($imagePath);
            }

            $news->delete();

            Log::info('News deleted successfully', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'news_id' => $id,
            ]);

            return $this->successResponse(
                data: null,
                message: 'News deleted successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to delete news', [
                'user_id' => $request->user()->id,
                'ip' => $request->ip(),
                'news_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }

    /**
     * Show a specific news item.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id)
    {
        try {
            $news = News::with('user')->find($id);
            if (!$news) {
                Log::info('News retrieval failed: News not found', [
                    'ip' => $request->ip(),
                    'news_id' => $id,
                ]);
                return $this->errorResponse('News not found', 404);
            }

            Log::info('News retrieved successfully', [
                'ip' => $request->ip(),
                'news_id' => $id,
            ]);

            return $this->successResponse(
                data: new NewsResource($news),
                message: 'News retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve news', [
                'ip' => $request->ip(),
                'news_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Internal server error', 500, $e->getMessage());
        }
    }
}