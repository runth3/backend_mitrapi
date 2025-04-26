<?php

namespace App\Http\Controllers;

use App\Http\Resources\NewsResource;
use App\Models\News;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 * name="News",
 * description="Operations related to news articles"
 * )
 */
class NewsController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     * path="/api/news",
     * summary="Get a list of news articles",
     * description="Retrieves a paginated list of news articles. Requires the X-Device-ID header for device identification.",
     * tags={"News"},
     * security={{"bearerAuth":{}}},
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="Number of items per page",
     * @OA\Schema(type="integer", default=20)
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(
     * property="data",
     * type="array",
     * @OA\Items(ref="#/components/schemas/NewsResource")
     * ),
     * @OA\Property(property="message", type="string", example="News list retrieved successfully"),
     * @OA\Property(property="meta", type="object",
     * @OA\Property(property="current_page", type="integer", example=1),
     * @OA\Property(property="per_page", type="integer", example=20),
     * @OA\Property(property="total", type="integer", example=100),
     * @OA\Property(property="last_page", type="integer", example=5),
     * @OA\Property(property="from", type="integer", example=1),
     * @OA\Property(property="to", type="integer", example=20)
     * )
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Device ID is required or invalid",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Device ID is required. Please include X-Device-ID in the request header."),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="device_id", type="array", @OA\Items(type="string"))
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal server error",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Internal server error"),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="exception", type="string", example="...")
     * )
     * )
     * )
     * )
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
     * @OA\Get(
     * path="/api/news/latest",
     * summary="Get the latest news articles",
     * description="Retrieves the 10 latest news articles. Requires the X-Device-ID header for device identification.",
     * tags={"News"},
     * security={{"bearerAuth":{}}},
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(
     * property="data",
     * type="array",
     * @OA\Items(ref="#/components/schemas/NewsResource")
     * ),
     * @OA\Property(property="message", type="string", example="Latest news retrieved successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Device ID is required or invalid",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Device ID is required. Please include X-Device-ID in the request header."),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="device_id", type="array", @OA\Items(type="string"))
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal server error",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Internal server error"),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="exception", type="string", example="...")
     * )
     * )
     * )
     * )
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
     * @OA\Post(
     * path="/api/news",
     * summary="Create a new news article",
     * description="Creates a new news article. Requires admin authentication and the X-Device-ID header.",
     * tags={"News"},
     * security={{"bearerAuth":{}}},
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * type="object",
     * required={"title", "content"},
     * @OA\Property(property="title", type="string", maxLength=255, example="Breaking News"),
     * @OA\Property(property="content", type="string", example="This is the content of the breaking news."),
     * @OA\Property(property="image", type="string", format="binary", description="Image file to upload (optional)")
     * )
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="News article created successfully",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", ref="#/components/schemas/NewsResource"),
     * @OA\Property(property="message", type="string", example="News created successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input or Device ID is required/invalid",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent(
    * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden: Admin access required",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Forbidden: Admin access required")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal server error",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Internal server error"),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="exception", type="string", example="...")
     * )
     * )
     * )
     * )
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
     * @OA\Put(
     * path="/api/news/{id}",
     * summary="Update an existing news article",
     * description="Updates an existing news article. Requires admin authentication and the X-Device-ID header.",
     * tags={"News"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID of the news article to update",
     * @OA\Schema(type="integer", format="int64")
     * ),
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * type="object",
     * @OA\Property(property="title", type="string", maxLength=255, example="Updated News Title"),
     * @OA\Property(property="content", type="string", example="This is the updated content."),
     * @OA\Property(property="image", type="string", format="binary", description="New image file to upload (optional)")
     * )
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="News article updated successfully",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", ref="#/components/schemas/NewsResource"),
     * @OA\Property(property="message", type="string", example="News updated successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Invalid input or Device ID is required/invalid",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Invalid input"),
     * @OA\Property(property="details", type="object")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden: Admin access required",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Forbidden: Admin access required")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="News article not found",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="News not found")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal server error",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Internal server error"),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="exception", type="string", example="...")
     * )
     * )
     * )
     * )
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
     * @OA\Delete(
     * path="/api/news/{id}",
     * summary="Delete a news article",
     * description="Deletes a news article. Requires admin authentication and the X-Device-ID header
     * tags={"News"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID of the news article to delete",
     * @OA\Schema(type="integer", format="int64")
     * ),
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\Response(
     * response=200,
     * description="News article deleted successfully",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="message", type="string", example="News deleted successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Device ID is required or invalid",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Device ID is required. Please include X-Device-ID in the request header."),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="device_id", type="array", @OA\Items(type="string"))
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     * ),
     * @OA\Response(
     * response=403,
     * description="Forbidden: Admin access required",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Forbidden: Admin access required")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="News article not found",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="News not found")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal server error",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Internal server error"),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="exception", type="string", example="...")
     * )
     * )
     * )
     * )
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
     * @OA\Get(
     * path="/api/news/{id}",
     * summary="Get a specific news article",
     * description="Retrieves a specific news article by its ID. Requires the X-Device-ID header for device identification.",
     * tags={"News"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID of the news article to retrieve",
     * @OA\Schema(type="integer", format="int64")
     * ),
     * @OA\Header(
     * header="X-Device-ID",
     * required=true,
     * description="Unique identifier of the device making the request (minimum 8 alphanumeric characters, underscores, or hyphens)"
     * ),
     * @OA\Response(
     * response=200,
     * description="Successful operation",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="success"),
     * @OA\Property(property="data", ref="#/components/schemas/NewsResource"),
     * @OA\Property(property="message", type="string", example="News retrieved successfully")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Device ID is required or invalid",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Device ID is required. Please include X-Device-ID in the request header."),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="device_id", type="array", @OA\Items(type="string"))
     * )
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Unauthorized",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Unauthorized")
     * )
     * ),
     * @OA\Response(
     * response=404,
     * description="News article not found",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="News not found")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal server error",
     * @OA\JsonContent(
     * type="object",
     * @OA\Property(property="status", type="string", example="error"),
     * @OA\Property(property="message", type="string", example="Internal server error"),
     * @OA\Property(property="details", type="object",
     * @OA\Property(property="exception", type="string", example="...")
     * )
     * )
     * )
     * )
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