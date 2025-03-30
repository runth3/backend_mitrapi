<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Attendance;
use App\Models\Performance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PerformanceController extends Controller
{
    // List all performances
    public function index(Request $request)
    {
        $performances = Performance::where('NIP', $request->user()->NIP)->get();
        return response()->json($performances);
    }

    // Create a new performance record
    public function store(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'nama' => 'required|string|max:255',
            'penjelasan' => 'nullable|string',
            'tglKinerja' => 'required|date',
            'durasiKinerjaMulai' => 'required|date_format:H:i',
            'durasiKinerjaSelesai' => 'required|date_format:H:i|after:durasiKinerjaMulai',
            'tupoksi' => 'nullable|string',
            'periodeKinerja' => 'nullable|string',
            'target' => 'nullable|integer',
            'satuanTarget' => 'nullable|string',
        ]);

        // Rule 1: tglKinerja must not be in the future
        if (Carbon::parse($request->tglKinerja)->isFuture()) {
            return response()->json(['message' => 'tglKinerja cannot be in the future'], 422);
        }

        // Rule 2: tglKinerja must exist in the user's attendance records
        $attendanceExists = Attendance::where('NIP', $user->NIP)
            ->where('date', $request->tglKinerja)
            ->exists();

        if (!$attendanceExists) {
            return response()->json(['message' => 'tglKinerja must match an attendance record'], 422);
        }

        // Calculate durasiKinerja and menitKinerja
        $start = Carbon::createFromFormat('H:i', $request->durasiKinerjaMulai);
        $end = Carbon::createFromFormat('H:i', $request->durasiKinerjaSelesai);
        $durationInMinutes = $end->diffInMinutes($start);
        $durationString = $end->diff($start)->format('%H:%I');

        $performance = Performance::create([
            'nama' => $request->nama,
            'penjelasan' => $request->penjelasan,
            'tglKinerja' => $request->tglKinerja,
            'durasiKinerjaMulai' => $request->durasiKinerjaMulai,
            'durasiKinerjaSelesai' => $request->durasiKinerjaSelesai,
            'durasiKinerja' => $durationString,
            'menitKinerja' => $durationInMinutes,
            'apv' => 'P', // Set approval status to Pending
            'tupoksi' => $request->tupoksi,
            'periodeKinerja' => $request->periodeKinerja,
            'target' => $request->target,
            'satuanTarget' => $request->satuanTarget,
            'NIP' => $user->NIP,
            'stsDel' => 0, // Default to not deleted
        ]);

        return response()->json($performance, 201);
    }

    // Show a specific performance record
    public function show($id)
    {
        $performance = Performance::findOrFail($id);
        return response()->json($performance);
    }

    // Update an existing performance record
    public function update(Request $request, $id)
    {
        $performance = Performance::findOrFail($id);

        // Restrict updates if apv is A (Approved) or R (Rejected)
        if (in_array($performance->apv, ['A', 'R'])) {
            return response()->json(['message' => 'Cannot update performance with apv status A or R'], 403);
        }

        $request->validate([
            'nama' => 'sometimes|required|string|max:255',
            'penjelasan' => 'nullable|string',
            'durasiKinerjaMulai' => 'nullable|date_format:H:i',
            'durasiKinerjaSelesai' => 'nullable|date_format:H:i|after:durasiKinerjaMulai',
        ]);

        if ($request->has('durasiKinerjaMulai') && $request->has('durasiKinerjaSelesai')) {
            $start = Carbon::createFromFormat('H:i', $request->durasiKinerjaMulai);
            $end = Carbon::createFromFormat('H:i', $request->durasiKinerjaSelesai);
            $performance->durasiKinerja = $end->diff($start)->format('%H:%I');
            $performance->menitKinerja = $end->diffInMinutes($start);
        }

        $performance->update($request->only([
            'nama',
            'penjelasan',
            'durasiKinerjaMulai',
            'durasiKinerjaSelesai',
            'tupoksi',
            'periodeKinerja',
            'target',
            'satuanTarget',
        ]));

        return response()->json($performance);
    }

    // Delete a performance record
    public function destroy($id)
    {
        $performance = Performance::findOrFail($id);

        // Perform a soft delete by setting stsDel to 1
        $performance->update(['stsDel' => 1]);

        return response()->json(['message' => 'Performance soft deleted successfully']);
    }

    public function me()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $currentMonth = Carbon::now()->format('Y-m');
        $cacheKey = "performance_{$user->username}_{$currentMonth}";

        // Retrieve performance data from cache or query the database if not cached
        $monthlyPerformance = Cache::remember($cacheKey, 300, function () use ($user, $currentMonth) {
            return Performance::where('NIP', $user->username)
                ->where('tglKinerja', 'like', "$currentMonth%")
                ->orderBy('tglKinerja', 'asc') // Sort by performance date
                ->get(); // Retrieve all fields allowed in the model's $fillable
        });

        return response()->json([
            'current_month' => [
                'month' => $currentMonth,
                'performances' => $monthlyPerformance,
            ],
        ]);
    }

    public function filterByApv(Request $request)
    {
        $request->validate([
            'month' => 'required|integer|min:1|max:12', // Month filter (1-12)
            'year' => 'required|integer|min:2000|max:2100', // Year filter
            'apvId' => 'nullable|string', // Optional filter for apv status
            'NIP' => 'nullable|string', // Optional filter for NIP
        ]);

        $month = str_pad($request->month, 2, '0', STR_PAD_LEFT); // Ensure month is two digits
        $year = $request->year;
        $apvId = $request->apvId;

        // Query performances with the specified filters
        $performances = Performance::where('stsDel', 0) // Exclude soft-deleted records
            ->whereYear('tglKinerja', $year) // Filter by year
            ->whereMonth('tglKinerja', $month) // Filter by month
            ->when($apvId !== null, function ($query) use ($apvId) {
                $query->where('apv', $apvId); // Filter by apv if provided
            })
            ->when($request->has('NIP'), function ($query) use ($request) {
                $query->where('NIP', $request->NIP); // Filter by NIP if provided
            })
            ->get();

        return response()->json($performances);
    }
}
