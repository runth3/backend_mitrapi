<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    $perPage = $request->input('per_page', 10); 
    $page = $request->input('page', 1); 
    $sortBy = $request->input('sort_by', 'created_at'); 
    
    // Handle sort direction
    $sortDesc = $request->has('sort_desc') ? 
        filter_var($request->input('sort_desc'), FILTER_VALIDATE_BOOLEAN) : 
        true; // Default to descending if not specified
    
    $search = $request->input('search', ''); 
    $isAdmin = $request->input('is_admin'); 

    // Log the incoming request
    Log::info('Sort Parameters:', [
        'sort_by' => $sortBy,
        'sort_desc' => $sortDesc,
        'raw_sort_desc' => $request->input('sort_desc'),
        'full_request' => $request->all()
    ]);

    // Build the query
    $query = User::query()
        ->when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when($isAdmin !== null, function ($query) use ($isAdmin) {
            $query->where('is_admin', $isAdmin);
        });

    // Apply sorting
    $direction = $sortDesc ? 'desc' : 'asc';
    $query->orderBy($sortBy, $direction);

    // Get paginated results
    $users = $query->paginate($perPage, ['*'], 'page', $page);

    // Return response with sort information
    return response()->json([
        'data' => $users->items(),
        'meta' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ],
        'sort' => [
            'sort_by' => $sortBy,
            'sort_desc' => $sortDesc,
            'direction' => $direction
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
        $news = User::findOrFail($id);
        return response()->json($news);
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
