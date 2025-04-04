<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DataPegawaiSimpeg;
use App\Models\DataPegawaiAbsen;
use App\Models\DataPegawaiEkinerja;
use App\Models\UserAbsen;
use App\Models\UserEkinerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        
        // Validate sort_by to prevent SQL injection
        $validSortColumns = ['name', 'email', 'username', 'created_at', 'updated_at'];
        $sortBy = in_array($request->input('sort_by'), $validSortColumns) 
            ? $request->input('sort_by') 
            : 'created_at';
    
        // Consistent boolean handling
        $sortDesc = $request->has('sort_desc') 
        ? filter_var($request->input('sort_desc'), FILTER_VALIDATE_BOOLEAN) 
        : false; // Default to ASC if not provided
    
        $isAdmin = $request->has('is_admin') 
            ? filter_var($request->input('is_admin'), FILTER_VALIDATE_BOOLEAN) 
            : null;
        // Log the sort direction for debugging
            Log::debug('Sort Direction Received:', [
                'sort_desc_raw' => $request->input('sort_desc'),
                'sort_desc_processed' => $sortDesc,
            ]);
        // Query remains the same
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
    
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
            // Optional: Include sort info for debugging
            'sort' => [
                'by' => $sortBy,
                'desc' => $sortDesc,
            ],
        ]);
    }


    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
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

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     */
    public function show($id)
    {
        $user = User::findOrFail($id); 

        $userAbsen = UserAbsen::where('name', $user->username)->first();
        $userEkinerja = UserEkinerja::where('UID', $user->username)->first();
        $dataPegawaiAbsen = DataPegawaiAbsen::where('nip', $user->username)->first();
        $dataPegawaiEkinerja = DataPegawaiEkinerja::where('nip', $user->username)->first();
        $dataPegawaiSimpeg = DataPegawaiSimpeg::where('nip', $user->username)->first();
        $dataPegawaiAbsen = DataPegawaiAbsen::where('nip', $user->username)->first();
        $dataPegawaiEkinerja = DataPegawaiEkinerja::where('nip', $user->username)->first();
        $faceModel = $user->faceModel; // Assuming you have a faceModel attribute in your User model
   // Prepare the response data
   $response = [
    'user' => [
        'name' => $user->name,
        'username' => $user->username,
        'email' => $user->email,
        'phone' => $user->phone,
        'dob' => $user->dob,
        'address' => $user->address,
        'created_at' => $user->created_at,
        'updated_at' => $user->updated_at,
    ],
    'dataPegawaiSimpeg' => $dataPegawaiSimpeg ? $dataPegawaiSimpeg->toArray() : null,
    'dataPegawaiAbsen' => $dataPegawaiAbsen ? $dataPegawaiAbsen->toArray() : null,
    'dataPegawaiEkinerja' => $dataPegawaiEkinerja ? $dataPegawaiEkinerja->toArray() : null,
    'userAbsen' => $userAbsen ? $userAbsen->toArray() : null,
    'userEkinerja' => $userEkinerja ? $userEkinerja->toArray() : null,
    'faceModel' => $faceModel ? $faceModel->toArray() : null,
    ];      

         return response()->json($response);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
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

        return response()->json($user);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
}
