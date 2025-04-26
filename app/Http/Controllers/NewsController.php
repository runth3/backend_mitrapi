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
     * Get a list of news articles.
     * Retrieves a paginated list of news articles. Requires the X-Device-ID header for device identification.
     */
    public function index(Request $request)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('News list retrieval failed: Device ID missing', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('News list retrieval failed: Invalid Device ID format', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('News list retrieval failed: User not authenticated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized',
                    statusCode: 401,
                    details: null
                );
            }

            $perPage = $request->input('per_page', 20);
            $news = News::with('user')->orderBy('created_at', 'desc')->paginate($perPage);

            Log::info('News list retrieved', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'per_page' => $perPage,
                'headers' => $request->headers->all(),
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
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Internal server error',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get the latest news articles.
     * Retrieves the 10 latest news articles. Requires the X-Device-ID header for device identification.
     */
    public function latest(Request $request)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('Latest news retrieval failed: Device ID missing', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('Latest news retrieval failed: Invalid Device ID format', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('Latest news retrieval failed: User not authenticated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized',
                    statusCode: 401,
                    details: null
                );
            }

            $news = News::with('user')->orderBy('created_at', 'desc')->take(10)->get();

            Log::info('Latest news retrieved', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: NewsResource::collection($news),
                message: 'Latest news retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve latest news', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Internal server error',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Create a new news article.
     * Creates a new news article. Requires admin authentication and the X-Device-ID header.
     */
    public function store(Request $request)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('News creation failed: Device ID missing', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('News creation failed: Invalid Device ID format', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('News creation failed: User not authenticated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized',
                    statusCode: 401,
                    details: null
                );
            }

            if (!$user->is_admin) {
                Log::warning('News creation failed: Admin access required', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Forbidden: Admin access required',
                    statusCode: 403,
                    details: null
                );
            }

            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                Log::info('News creation failed: Validation error', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'errors' => $validator->errors()->toArray(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid input',
                    statusCode: 400,
                    details: $validator->errors()->toArray()
                );
            }

            $imageUrl = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('news_images', 'public');
                $imageUrl = Storage::url($imagePath);
            }

            $news = News::create([
                'title' => $request->title,
                'content' => $request->content,
                'image_url' => $imageUrl,
                'user_id' => $user->id,
            ]);

            Log::info('News created successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $news->id,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new NewsResource($news->load('user')),
                message: 'News created successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Exception $e) {
            Log::error('Failed to create news', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Internal server error',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Update an existing news article.
     * Updates an existing news article. Requires admin authentication and the X-Device-ID header.
     */
    public function update(Request $request, $id)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('News update failed: Device ID missing', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('News update failed: Invalid Device ID format', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('News update failed: User not authenticated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized',
                    statusCode: 401,
                    details: null
                );
            }

            if (!$user->is_admin) {
                Log::warning('News update failed: Admin access required', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Forbidden: Admin access required',
                    statusCode: 403,
                    details: null
                );
            }

            $news = News::find($id);
            if (!$news) {
                Log::info('News update failed: News not found', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'News not found',
                    statusCode: 404,
                    details: null
                );
            }

            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|required|string|max:255',
                'content' => 'sometimes|required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validator->fails()) {
                Log::info('News update failed: Validation error', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'errors' => $validator->errors()->toArray(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid input',
                    statusCode: 400,
                    details: $validator->errors()->toArray()
                );
            }

            $data = $request->only(['title', 'content']);
            if ($request->hasFile('image')) {
                if ($news->image_url) {
                    $oldImagePath = str_replace(Storage::url(''), '', $news->image_url);
                    Storage::disk('public')->delete($oldImagePath);
                }
                $imagePath = $request->file('image')->store('news_images', 'public');
                $data['image_url'] = Storage::url($imagePath);
            }

            $news->update($data);

            Log::info('News updated successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $news->id,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new NewsResource($news->load('user')),
                message: 'News updated successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to update news', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $id,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Internal server error',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Delete a news article.
     * Deletes a news article. Requires admin authentication and the X-Device-ID header.
     */
    public function destroy(Request $request, $id)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('News deletion failed: Device ID missing', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('News deletion failed: Invalid Device ID format', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('News deletion failed: User not authenticated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized',
                    statusCode: 401,
                    details: null
                );
            }

            if (!$user->is_admin) {
                Log::warning('News deletion failed: Admin access required', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Forbidden: Admin access required',
                    statusCode: 403,
                    details: null
                );
            }

            $news = News::find($id);
            if (!$news) {
                Log::info('News deletion failed: News not found', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'News not found',
                    statusCode: 404,
                    details: null
                );
            }

            if ($news->image_url) {
                $imagePath = str_replace(Storage::url(''), '', $news->image_url);
                Storage::disk('public')->delete($imagePath);
            }

            $news->delete();

            Log::info('News deleted successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: null,
                message: 'News deleted successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to delete news', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $id,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Internal server error',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Get a specific news article.
     * Retrieves a specific news article by its ID. Requires the X-Device-ID header for device identification.
     */
    public function show(Request $request, $id)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('News retrieval failed: Device ID missing', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Please include X-Device-ID in the request header.',
                statusCode: 400,
                details: ['device_id' => 'The X-Device-ID header is required.']
            );
        }
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('News retrieval failed: Invalid Device ID format', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        try {
            $user = $request->user();
            if (!$user) {
                Log::warning('News retrieval failed: User not authenticated', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Unauthorized',
                    statusCode: 401,
                    details: null
                );
            }

            $news = News::with('user')->find($id);
            if (!$news) {
                Log::info('News retrieval failed: News not found', [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $deviceId,
                    'news_id' => $id,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'News not found',
                    statusCode: 404,
                    details: null
                );
            }

            Log::info('News retrieved successfully', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $id,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new NewsResource($news),
                message: 'News retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve news', [
                'user_id' => $request->user()->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'news_id' => $id,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Internal server error',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }
}