<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Models\User;
use App\Models\DataPegawaiSimpeg;
use App\Models\DataPegawaiAbsen;
use App\Models\DataPegawaiEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    use ApiResponseTrait;

    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        try {
            $validSortColumns = ['name', 'email', 'username', 'created_at', 'updated_at'];
            $sortBy = in_array($request->input('sort_by'), $validSortColumns)
                ? $request->input('sort_by')
                : 'created_at';

            $sortDesc = $request->has('sort_desc')
                ? filter_var($request->input('sort_desc'), FILTER_VALIDATE_BOOLEAN)
                : false;

            $isAdmin = $request->has('is_admin')
                ? filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOLEAN)
                : null;

            Log::debug('User index requested', [
                'sort_by' => $sortBy,
                'sort_desc' => $sortDesc,
                'is_admin' => $isAdmin,
                'search' => $request->input('search'),
                'per_page' => $request->input('per_page', 10),
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            $query = User::query()
                ->when($request->input('search'), function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when($isAdmin !== null, function ($query) use ($isAdmin) {
                    $query->where('is_admin', $isAdmin);
                });

            $users = $query->orderBy($sortBy, $sortDesc ? 'desc' : 'asc')
                          ->paginate($request->input('per_page', 10));

            return $this->successResponse(
                data: new UserCollection($users),
                message: 'Users retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve users', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to retrieve users',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'is_admin' => 'required|boolean',
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'is_admin' => $validated['is_admin'],
            ]);

            Log::info('User created', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new UserResource($user),
                message: 'User created successfully',
                meta: null,
                statusCode: 201
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info('User creation failed: Validation error', [
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
            Log::error('User creation failed', [
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to create user',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Display the specified user.
     */
    public function show(Request $request, $id)
    {
        try {
            $user = User::with('faceModel')->find($id);

            if (!$user) {
                Log::warning('User not found', [
                    'user_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'User not found',
                    statusCode: 404,
                    details: null
                );
            }

            $userAbsen = UserAbsen::where('name', $user->username)->first();
            $userEkinerja = UserEkinerja::where('UID', $user->username)->first();
            $dataPegawaiAbsen = DataPegawaiAbsen::where('nip', $user->username)->first();
            $dataPegawaiEkinerja = DataPegawaiEkinerja::where('nip', $user->username)->first();
            $dataPegawaiSimpeg = DataPegawaiSimpeg::where('nip', $user->username)->first();

            // Attach related data to the user model for resource
            $user->dataPegawaiSimpeg = $dataPegawaiSimpeg;
            $user->dataPegawaiAbsen = $dataPegawaiAbsen;
            $user->dataPegawaiEkinerja = $dataPegawaiEkinerja;
            $user->userAbsen = $userAbsen;
            $user->userEkinerja = $userEkinerja;

            Log::info('User retrieved', [
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new UserResource($user),
                message: 'User retrieved successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user', [
                'user_id' => $id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to retrieve user',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                Log::warning('User not found', [
                    'user_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'User not found',
                    statusCode: 404,
                    details: null
                );
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:8',
                'is_admin' => 'sometimes|boolean',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $user->update($validated);

            Log::info('User updated', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: new UserResource($user),
                message: 'User updated successfully',
                meta: null,
                statusCode: 200
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::info('User update failed: Validation error', [
                'user_id' => $id,
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
            Log::error('User update failed', [
                'user_id' => $id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to update user',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }

    /**
     * Remove the specified user.
     */
    public function destroy(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                Log::warning('User not found', [
                    'user_id' => $id,
                    'ip' => $request->ip(),
                    'headers' => $request->headers->all(),
                ]);
                return $this->errorResponse(
                    message: 'User not found',
                    statusCode: 404,
                    details: null
                );
            }

            $user->delete();

            Log::info('User deleted', [
                'user_id' => $id,
                'email' => $user->email,
                'ip' => $request->ip(),
                'headers' => $request->headers->all(),
            ]);

            return $this->successResponse(
                data: null,
                message: 'User deleted successfully',
                meta: null,
                statusCode: 204
            );
        } catch (\Exception $e) {
            Log::error('User deletion failed', [
                'user_id' => $id,
                'ip' => $request->ip(),
                'error' => $e->getMessage(),
                'headers' => $request->headers->all(),
            ]);
            return $this->errorResponse(
                message: 'Failed to delete user',
                statusCode: 500,
                details: ['exception' => $e->getMessage()]
            );
        }
    }
}