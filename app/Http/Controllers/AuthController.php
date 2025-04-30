<?php
namespace App\Http\Controllers;

use App\Http\Resources\OfficeResource;
use App\Http\Resources\PegawaiResource;
use App\Http\Resources\DataOfficeEkinerjaResource;
use App\Models\DataOfficeEkinerja;
use App\Http\Resources\AttendanceCollection;
use App\Http\Resources\PerformanceCollection;
use App\Http\Resources\NewsCollection;
use App\Models\User;
use App\Models\DataPegawaiSimpeg;
use App\Models\Performance;
use App\Models\Attendance;
use App\Models\News;
use App\Models\DataOfficeKoordinat;
use App\Models\DataOfficeSimpeg;
use App\Models\FaceModel;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\RefreshToken;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * Authenticate user and get token with additional data.
     * Authenticates a user and returns an access token, refresh token, and additional data (office, face model, attendance, performance).
     * Requires the X-Device-ID header.
     */
  
     public function loginWithData(Request $request)
     {
         $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');
         if (!$deviceId) {
             Log::warning('Login with data failed: Device ID missing', [
                 'username' => $request->username,
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Device ID is required. Include X-Device-ID header or device_id in body.',
                 statusCode: 400,
                 details: ['device_id' => 'The device_id is required.']
             );
         }
 
         if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
             Log::warning('Login with data failed: Invalid Device ID format', [
                 'username' => $request->username,
                 'ip' => $request->ip(),
                 'device_id' => $deviceId,
                 'user_agent' => $request->userAgent(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Invalid Device ID format. Must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                 statusCode: 400,
                 details: ['device_id' => 'Invalid Device ID format.']
             );
         }
 
         $rateLimitKey = 'login:' . $request->username . ':' . $deviceId;
         if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
             Log::warning('Login with data rate limit exceeded', [
                 'username' => $request->username,
                 'ip' => $request->ip(),
                 'device_id' => $deviceId,
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Too many login attempts. Please try again later.',
                 statusCode: 429,
                 details: ['retry_after' => RateLimiter::availableIn($rateLimitKey)],
                 meta: ['retry_after' => RateLimiter::availableIn($rateLimitKey)]
             );
         }
 
         $validator = Validator::make($request->all(), [
             'username' => 'required|string',
             'password' => 'required|string',
             'device_id' => 'required|string|min:8',
         ]);
 
         if ($validator->fails()) {
             RateLimiter::increment($rateLimitKey, 60);
             Log::info('Login with data failed: Validation error', [
                 'username' => $request->username,
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'device_id' => $deviceId,
                 'errors' => $validator->errors()->toArray(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Invalid input. Please check your username, password, and device_id.',
                 statusCode: 400,
                 details: $validator->errors()->toArray()
             );
         }
 
         Log::info('Login with data attempt', [
             'username' => $request->username,
             'ip' => $request->ip(),
             'user_agent' => $request->userAgent(),
             'device_id' => $deviceId,
             'headers' => $request->headers->all(),
         ]);
 
         $user = User::where('username', $request->username)->first();
 
         if (!$user || !Hash::check($request->password, $user->password)) {
             RateLimiter::increment($rateLimitKey, 60);
             Log::info('Login with data failed: Invalid credentials', [
                 'username' => $request->username,
                 'ip' => $request->ip(),
                 'device_id' => $deviceId,
                 'user_agent' => $request->userAgent(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Invalid login credentials.',
                 statusCode: 401,
                 details: null
             );
         }
 
         try {
             RateLimiter::clear($rateLimitKey);
 
             // Buat access token
             $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));
             $accessToken = $tokenResult->accessToken;
             $accessToken->forceFill(['device_id' => $deviceId])->save();
             $accessTokenString = $tokenResult->plainTextToken;
 
             // Buat refresh token
             $refreshToken = $this->generateRefreshToken($user, $deviceId);
 
             // Ambil data tambahan
             $dataPegawai = DataPegawaiSimpeg::where('nip', $user->username)->first();
             $dataOffice = DataOfficeSimpeg::where('id_instansi', $dataPegawai ? $dataPegawai->id_instansi : null)->first();
             $coordinates = $dataPegawai ? DataOfficeKoordinat::where('id_instansi', $dataPegawai->id_instansi)
                 ->where('aktif', 0)
                 ->first() : null;
             $performanceConfig = $dataPegawai ? DataOfficeEkinerja::where('id', $dataPegawai->id_instansi)->first() : null;
             $faceModel = FaceModel::where('user_id', $user->id)
                 ->where('is_active', true)
                 ->latest('updated_at')
                 ->first();
             $attendances = Attendance::where('nip_pegawai', $user->username)
                 ->whereMonth('checktime', now()->month)
                 ->whereYear('checktime', now()->year)
                 ->get();
             $performances = Performance::where('NIP', $user->username)
                 ->whereMonth('tglKinerja', now()->month)
                 ->whereYear('tglKinerja', now()->year)
                 ->get();
             $news = News::orderBy('published_at', 'desc')->take(10)->get();
 
             $response = [
                 'access_token' => $accessTokenString,
                 'refresh_token' => $refreshToken->token,
                 'token_type' => 'Bearer',
                 'expires_at' => now()->addDays(7)->toIso8601String(),
                 'device_id' => $deviceId,
                 'user' => [
                     'id' => $user->id,
                     'username' => $user->username,
                     'email' => $user->email,
                 ],
                 'profile' => $dataPegawai ? new PegawaiResource($dataPegawai) : null,
                 'office' => $dataOffice ? new OfficeResource($dataOffice, $coordinates, $performanceConfig) : null,
                 'face_model' => $faceModel ? [
                     'url' => Storage::disk('local')->temporaryUrl($faceModel->image_path, now()->addMinutes(60)),
                     'last_updated' => $faceModel->updated_at->toIso8601String(),
                 ] : null,
                 'attendance' => new AttendanceCollection($attendances),
                 'performance' => new PerformanceCollection($performances),
                 'news' => new NewsCollection($news),
             ];
 
             Log::info('Login with data success', [
                 'user_id' => $user->id,
                 'username' => $user->username,
                 'ip' => $request->ip(),
                 'device_id' => $deviceId,
                 'user_agent' => $request->userAgent(),
                 'refresh_token_length' => strlen($refreshToken->token), // Tambah logging
                 'headers' => $request->headers->all(),
             ]);
 
             return $this->successResponse(
                 data: $response,
                 message: 'Login successful',
                 meta: null,
                 statusCode: 200
             );
         } catch (\Exception $e) {
             RateLimiter::increment($rateLimitKey, 60);
             Log::error('Login with data failed: Server error', [
                 'user_id' => $user->id ?? 'unknown',
                 'username' => $request->username,
                 'ip' => $request->ip(),
                 'device_id' => $deviceId,
                 'error' => $e->getMessage(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Failed to process login.',
                 statusCode: 500,
                 details: ['exception' => $e->getMessage()]
             );
         }
     }
 
     /**
     * Refresh token and get new token with additional data.
     * Validates refresh token, issues a new access token and refresh token, and returns additional data (profile, office, face model, attendance, performance, news).
     * Requires the X-Device-ID header.
     */
    public function refreshWithData(Request $request)
    {
        $deviceId = $request->header('X-Device-ID') ?? $request->input('device_id');
        if (!$deviceId) {
            Log::warning('Refresh with data failed: Device ID missing', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Device ID is required. Include X-Device-ID header or device_id in body.',
                statusCode: 400,
                details: ['device_id' => 'The device_id is required.']
            );
        }

        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('Refresh with data failed: Invalid Device ID format', [
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid Device ID format. Must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                statusCode: 400,
                details: ['device_id' => 'Invalid Device ID format.']
            );
        }

        $rateLimitKey = 'refresh:' . $deviceId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            Log::warning('Refresh with data rate limit exceeded', [
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Too many refresh attempts. Please try again later.',
                statusCode: 429,
                details: ['retry_after' => RateLimiter::availableIn($rateLimitKey)],
                meta: ['retry_after' => RateLimiter::availableIn($rateLimitKey)]
            );
        }

        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string|min:32',
            'device_id' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::info('Refresh with data failed: Validation error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'errors' => $validator->errors()->toArray(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please check your refresh_token and device_id.',
                statusCode: 400,
                details: $validator->errors()->toArray()
            );
        }

        Log::info('Refresh with data attempt', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_id' => $deviceId,
            'refresh_token_length' => strlen($request->refresh_token), // Log panjang token untuk debug
            'headers' => $request->headers->all(),
        ]);

        try {
            $refreshToken = RefreshToken::where('token', $request->refresh_token)
                ->where('device_id', $deviceId)
                ->where('expires_at', '>', now())
                ->first();

            if (!$refreshToken) {
                RateLimiter::increment($rateLimitKey, 60);
                Log::info('Refresh with data failed: Invalid or expired refresh token', [
                    'ip' => $request->ip(),
                    'device_id' => $deviceId,
                    'user_agent' => $request->userAgent(),
                    'refresh_token_length' => strlen($request->refresh_token),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid or expired refresh token.',
                    statusCode: 401,
                    details: null
                );
            }

            $user = User::find($refreshToken->user_id);

            if (!$user) {
                RateLimiter::increment($rateLimitKey, 60);
                Log::info('Refresh with data failed: User not found', [
                    'ip' => $request->ip(),
                    'device_id' => $deviceId,
                    'user_agent' => $request->userAgent(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'User not found.',
                    statusCode: 401,
                    details: null
                );
            }

            RateLimiter::clear($rateLimitKey);

            // Hapus refresh token lama
            $refreshToken->delete();

            // Buat access token baru
            $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));
            $accessToken = $tokenResult->accessToken;
            $accessToken->forceFill(['device_id' => $deviceId])->save();
            $accessTokenString = $tokenResult->plainTextToken;

            // Buat refresh token baru
            $newRefreshToken = $this->generateRefreshToken($user, $deviceId);

            // Ambil data tambahan
            $dataPegawai = DataPegawaiSimpeg::where('nip', $user->username)->first();
            $dataOffice = DataOfficeSimpeg::where('id_instansi', $dataPegawai ? $dataPegawai->id_instansi : null)->first();
            $coordinates = $dataPegawai ? DataOfficeKoordinat::where('id_instansi', $dataPegawai->id_instansi)
                ->where('aktif', 0)
                ->first() : null;
            $performanceConfig = $dataPegawai ? DataOfficeEkinerja::where('id', $dataPegawai->id_instansi)->first() : null;
            $faceModel = FaceModel::where('user_id', $user->id)
                ->where('is_active', true)
                ->latest('updated_at')
                ->first();
            $attendances = Attendance::where('nip_pegawai', $user->username)
                ->whereMonth('checktime', now()->month)
                ->whereYear('checktime', now()->year)
                ->get();
            $performances = Performance::where('NIP', $user->username)
                ->whereMonth('tglKinerja', now()->month)
                ->whereYear('tglKinerja', now()->year)
                ->get();
            $news = News::orderBy('published_at', 'desc')->take(10)->get();

            $response = [
                'access_token' => $accessTokenString,
                'refresh_token' => $newRefreshToken->token,
                'token_type' => 'Bearer',
                'expires_at' => now()->addDays(7)->toIso8601String(),
                'device_id' => $deviceId,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                ],
                'profile' => $dataPegawai ? new PegawaiResource($dataPegawai) : null,
                'office' => $dataOffice ? new OfficeResource($dataOffice, $coordinates, $performanceConfig) : null,
                'face_model' => $faceModel ? [
                    'url' => Storage::disk('local')->temporaryUrl($faceModel->image_path, now()->addMinutes(60)),
                    'last_updated' => $faceModel->updated_at->toIso8601String(),
                ] : null,
                'attendance' => new AttendanceCollection($attendances),
                'performance' => new PerformanceCollection($performances),
                'news' => new NewsCollection($news),
            ];

            Log::info('Refresh with data success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: $response,
                message: 'Token refreshed successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::error('Refresh with data failed: Server error', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to process refresh.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }
    /**
     * Authenticate user and get token.
     * Authenticates a user using username and password and returns an access token and refresh token.
     * Requires the X-Device-ID header.
     */
    public function login(Request $request)
    {
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('Login failed: Device ID missing', [
                'username' => $request->username,
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
            Log::warning('Login failed: Invalid Device ID format', [
                'username' => $request->username,
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
    
        $rateLimitKey = 'login:' . $request->username . ':' . $deviceId;
        if (RateLimiter::tooManyAttempts($rateLimitKey, 5)) {
            Log::warning('Login rate limit exceeded', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Too many login attempts. Please try again later.',
                statusCode: 429,
                details: ['retry_after' => RateLimiter::availableIn($rateLimitKey)],
                meta: ['retry_after' => RateLimiter::availableIn($rateLimitKey)]
            );
        }
    
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::info('Login failed: Validation error', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'errors' => $validator->errors()->toArray(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid input. Please check your username and password.',
                statusCode: 400,
                details: $validator->errors()->toArray()
            );
        }
    
        Log::info('Login attempt', [
            'username' => $request->username,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_id' => $deviceId,
            'headers' => $request->headers->all(),
        ]);
    
        $user = User::where('username', $request->username)->first();
    
        if (!$user || !Hash::check($request->password, $user->password)) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::info('Login failed: Invalid credentials', [
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Invalid login credentials.',
                statusCode: 401,
                details: null
            );
        }
    
        try {
            RateLimiter::clear($rateLimitKey);
            // Buat access token
            $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));
            $accessToken = $tokenResult->accessToken;
            // Simpan device_id secara eksplisit
            $accessToken->forceFill(['device_id' => $deviceId])->save();
            $accessTokenString = $tokenResult->plainTextToken;
    
            $refreshToken = $this->generateRefreshToken($user, $deviceId);
    
            Log::info('Login success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'user_agent' => $request->userAgent(),
                'headers' => $request->headers->all(),
            ]);
    
            return $this->successResponse(
                data: [
                    'access_token' => $accessTokenString,
                    'token_type' => 'Bearer',
                    'refresh_token' => $refreshToken->token,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
                ],
                message: 'Login successful',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            RateLimiter::increment($rateLimitKey, 60);
            Log::error('Login failed: Token creation error', [
                'user_id' => $user->id ?? 'unknown',
                'username' => $request->username,
                'ip' => $request->ip(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to create authentication token.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }
    

    /**
     * Refresh access token.
     * Refreshes an expired access token using a valid refresh token. Requires the X-Device-ID header.
     */
    

     public function refresh(Request $request)
     {
         $deviceId = $request->header('X-Device-ID');
         if (!$deviceId) {
             Log::warning('Refresh failed: Device ID missing', [
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
             Log::warning('Refresh failed: Invalid Device ID format', [
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
     
         $validator = Validator::make($request->all(), [
             'refresh_token' => 'required|string',
         ]);
     
         if ($validator->fails()) {
             Log::info('Refresh failed: Validation error', [
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'device_id' => $deviceId,
                 'errors' => $validator->errors()->toArray(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Invalid input. Please provide a valid refresh token.',
                 statusCode: 400,
                 details: $validator->errors()->toArray()
             );
         }
     
         $refreshToken = RefreshToken::where('token', $request->refresh_token)
             ->where('device_id', $deviceId)
             ->first();
     
         if (!$refreshToken || $refreshToken->expires_at < Carbon::now()) {
             Log::info('Refresh failed: Invalid or expired refresh token', [
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'device_id' => $deviceId,
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Invalid or expired refresh token. Please login again.',
                 statusCode: 401,
                 details: null
             );
         }
     
         $user = User::find($refreshToken->user_id);
     
         if (!$user) {
             Log::info('Refresh failed: User not found', [
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'device_id' => $deviceId,
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'User not found.',
                 statusCode: 404,
                 details: null
             );
         }
     
         try {
             $user->tokens()->where('device_id', $deviceId)->delete();
             $refreshToken->delete();
     
             // Buat access token baru
             $tokenResult = $user->createToken('auth_token', ['*'], now()->addDays(7));
             $newAccessToken = $tokenResult->accessToken;
             $newAccessToken->forceFill(['device_id' => $deviceId])->save();
             $newAccessTokenString = $tokenResult->plainTextToken;
     
             $newRefreshToken = $this->generateRefreshToken($user, $deviceId);
     
             Log::info('Token refreshed', [
                 'user_id' => $user->id,
                 'username' => $user->username,
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'device_id' => $deviceId,
                 'headers' => $request->headers->all(),
             ]);
     
             return $this->successResponse(
                 data: [
                     'access_token' => $newAccessTokenString,
                     'token_type' => 'Bearer',
                     'refresh_token' => $newRefreshToken->token,
                     'expires_at' => Carbon::now()->addDays(7)->toIso8601String(),
                 ],
                 message: 'Token refreshed successfully',
                 meta: null,
                 statusCode: 200
             );
         } catch (\Exception $e) {
             Log::error('Refresh failed: Token creation error', [
                 'user_id' => $user->id ?? 'unknown',
                 'ip' => $request->ip(),
                 'user_agent' => $request->userAgent(),
                 'device_id' => $deviceId,
                 'error' => $e->getMessage(),
                 'headers' => $request->headers->all(),
             ]);
             return $this->errorResponse(
                 message: 'Failed to refresh token.',
                 statusCode: 500,
                 details: ['exception' => $e->getMessage()]
             );
         }
     }
    /**
     * Logout user.
     * Invalidates the current access token and refresh token for the authenticated user.
     * Requires the X-Device-ID header.
     */
    public function logout(Request $request)
    {
        // Validasi X-Device-ID
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            Log::warning('Logout failed: Device ID missing', [
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

        // Validasi format Device ID
        if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $deviceId)) {
            Log::warning('Logout failed: Invalid Device ID format', [
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

        $user = $request->user();
        if (!$user) {
            Log::warning('Logout failed: User not authenticated', [
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

        try {
            $user->tokens()->where('device_id', $deviceId)->delete();
            RefreshToken::where('user_id', $user->id)->where('device_id', $deviceId)->delete();

            Log::info('Logout success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: null,
                message: 'Logged out successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'user_id' => $user->id ?? 'unknown',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to logout.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Validate access token.
     * Validates an access token provided in the Authorization: Bearer <token> header.
     * Requires the X-Device-ID header for device validation.
     */
    public function validateToken(Request $request)
    {
        try {
            // Validasi X-Device-ID
            $currentDeviceId = $request->header('X-Device-ID');
            if (!$currentDeviceId) {
                Log::warning('Token validation failed: Device ID missing', [
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

            // Validasi format Device ID
            if (!preg_match('/^[a-zA-Z0-9_-]{8,}$/', $currentDeviceId)) {
                Log::warning('Token validation failed: Invalid Device ID format', [
                    'ip' => $request->ip(),
                    'device_id' => $currentDeviceId,
                    'user_agent' => $request->userAgent(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid Device ID format. Device ID must be at least 8 characters and contain only alphanumeric characters, underscores, or hyphens.',
                    statusCode: 400,
                    details: ['device_id' => 'Invalid Device ID format.']
                );
            }

            // Ambil token dari header Authorization
            $tokenString = $request->bearerToken();
            if (!$tokenString) {
                Log::info('Token validation failed: No token provided', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'No token provided. Please include a valid Bearer token.',
                    statusCode: 401,
                    details: null
                );
            }

            // Cari token di personal_access_tokens
            $token = PersonalAccessToken::findToken($tokenString);
            if (!$token) {
                Log::info('Token validation failed: Invalid token', [
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Invalid token. Please login again.',
                    statusCode: 401,
                    details: null
                );
            }

            // Periksa apakah token kedaluwarsa
            if ($token->expires_at && $token->expires_at->isPast()) {
                Log::info('Token validation failed: Token expired', [
                    'ip' => $request->ip(),
                    'token_id' => $token->id,
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Token has expired. Please login again.',
                    statusCode: 401,
                    details: null
                );
            }

            // Ambil pengguna terkait
            $user = $token->tokenable;
            if (!$user || !($user instanceof User)) {
                Log::info('Token validation failed: User not found', [
                    'ip' => $request->ip(),
                    'token_id' => $token->id,
                    'user_agent' => $request->userAgent(),
                    'device_id' => $currentDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'User not found for this token.',
                    statusCode: 404,
                    details: null
                );
            }

            // Validasi perangkat
            $tokenDeviceId = $token->device_id;
            if ($tokenDeviceId !== $currentDeviceId) {
                Log::warning('Token validation failed: Device changed', [
                    'user_id' => $user->id,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'current_device_id' => $currentDeviceId,
                    'token_device_id' => $tokenDeviceId,
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'Token invalid: Device changed. Please login again from this device.',
                    statusCode: 401,
                    details: ['device_id' => 'Device ID does not match the token.'],
                    meta: [
                        'current_device_id' => $currentDeviceId,
                        'expected_device_id' => $tokenDeviceId,
                    ]
                );
            }

            Log::info('Token validation success', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'token_id' => $token->id,
                'device_id' => $currentDeviceId,
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: [
                    'is_valid' => true,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                    ],
                    'token' => [
                        'id' => $token->id,
                        'expires_at' => $token->expires_at ? $token->expires_at->toIso8601String() : null,
                        'last_used_at' => $token->last_used_at ? $token->last_used_at->toIso8601String() : null,
                        'device_id' => $tokenDeviceId,
                    ],
                ],
                message: 'Token is valid',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Token validation failed: Internal error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device_id' => $request->header('X-Device-ID', 'unknown'),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to validate token due to an internal error. Please try again.',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Generate a new refresh token for the user.
     */
    protected function generateRefreshToken(User $user, string $deviceId): RefreshToken
    {
        // Limit number of refresh tokens (max 5 per user and device)
        $existingTokens = RefreshToken::where('user_id', $user->id)
            ->where('device_id', $deviceId)
            ->count();
        if ($existingTokens >= 5) {
            RefreshToken::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->oldest()
                ->delete();
        }

        return RefreshToken::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'device_id' => $deviceId,
            'expires_at' => Carbon::now()->addDays(30),
        ]);
    }
}