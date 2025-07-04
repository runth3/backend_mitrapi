<?php

namespace App\Http\Controllers;

use App\Models\ApplicationLetter;
use App\Http\Resources\ApplicationLetterResource;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ApplicationLetterController extends Controller
{
    use ApiResponseTrait;

    public function checkApproval(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'nip_pegawai' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $date = $request->date;
            $nipPegawai = $request->nip_pegawai ?? $user->username;

            // Check if there's an approved application letter for the date and employee
            $applicationLetter = ApplicationLetter::where('nip_pegawai', $nipPegawai)
                ->where('tgl_mulai', '<=', $date)
                ->where('tgl_selesai', '>=', $date)
                ->where('status', 2) // 2 = approved
                ->first();

            $hasApproval = $applicationLetter !== null;

            return $this->successResponse(
                data: [
                    'date' => $date,
                    'nip_pegawai' => $nipPegawai,
                    'has_approval' => $hasApproval,
                    'application_info' => $hasApproval ? [
                        'id' => $applicationLetter->id_data_permohonan,
                        'jenis_permohonan' => $applicationLetter->jenis_permohonan,
                        'start_date' => $applicationLetter->tgl_mulai->format('Y-m-d'),
                        'end_date' => $applicationLetter->tgl_selesai->format('Y-m-d'),
                        'description' => $applicationLetter->deskripsi,
                        'approved_by' => $applicationLetter->aprv_by,
                        'approved_on' => $applicationLetter->aprv_on?->format('Y-m-d H:i:s')
                    ] : null
                ],
                message: $hasApproval ? 'Application letter found for this date' : 'No approved application letter found for this date'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check application approval', 500, $e->getMessage());
        }
    }

    public function listCurrentMonth(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'nip_pegawai' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $nipPegawai = $request->nip_pegawai ?? $user->username;
            $currentMonth = now()->format('Y-m');

            // Get all application letters for current month
            $applicationLetters = ApplicationLetter::where('nip_pegawai', $nipPegawai)
                ->where(function ($query) use ($currentMonth) {
                    $query->whereRaw('DATE_FORMAT(tgl_mulai, "%Y-%m") = ?', [$currentMonth])
                          ->orWhereRaw('DATE_FORMAT(tgl_selesai, "%Y-%m") = ?', [$currentMonth]);
                })
                ->orderBy('tgl_mulai', 'desc')
                ->get();

            return $this->successResponse(
                data: [
                    'month' => $currentMonth,
                    'nip_pegawai' => $nipPegawai,
                    'applications' => $applicationLetters->map(function ($app) {
                        return [
                            'id' => $app->id_data_permohonan,
                            'jenis_permohonan' => $app->jenis_permohonan,
                            'start_date' => $app->tgl_mulai->format('Y-m-d'),
                            'end_date' => $app->tgl_selesai->format('Y-m-d'),
                            'description' => $app->deskripsi,
                            'status' => $app->status, // 1=new, 2=approved
                            'status_text' => $app->status == 1 ? 'New' : 'Approved',
                            'created_on' => $app->cre_on?->format('Y-m-d H:i:s'),
                            'approved_by' => $app->aprv_by,
                            'approved_on' => $app->aprv_on?->format('Y-m-d H:i:s'),
                            'rejected_by' => $app->reject_by,
                            'rejected_on' => $app->reject_on?->format('Y-m-d H:i:s'),
                            'reason' => $app->alasan
                        ];
                    })
                ],
                message: 'Application letters retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve application letters', 500, $e->getMessage());
        }
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $perPage = $request->input('per_page', 20);
            $applications = ApplicationLetter::where('nip_pegawai', $user->username)
                ->orderBy('tgl_mulai', 'desc')
                ->paginate($perPage);

            return $this->successResponse(
                data: ApplicationLetterResource::collection($applications),
                message: 'Application letters retrieved successfully',
                meta: [
                    'current_page' => $applications->currentPage(),
                    'per_page' => $applications->perPage(),
                    'total' => $applications->total(),
                    'last_page' => $applications->lastPage()
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve application letters', 500, $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'jenis_permohonan' => 'required|string|max:10',
                'tgl_mulai' => 'required|date',
                'tgl_selesai' => 'required|date|after_or_equal:tgl_mulai',
                'deskripsi' => 'required|string',
                'alasan' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 422, $validator->errors()->toArray());
            }

            $idDataPermohonan = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 13));

            $application = ApplicationLetter::create([
                'id_data_permohonan' => $idDataPermohonan,
                'nip_pegawai' => $user->username,
                'jenis_permohonan' => $request->jenis_permohonan,
                'tgl_mulai' => $request->tgl_mulai,
                'tgl_selesai' => $request->tgl_selesai,
                'deskripsi' => $request->deskripsi,
                'alasan' => $request->alasan,
                'status' => 1, // 1 = new/pending
                'cre_by' => $user->username,
                'cre_on' => Carbon::now('Asia/Makassar')
            ]);

            return $this->successResponse(
                data: new ApplicationLetterResource($application),
                message: 'Application letter created successfully',
                statusCode: 201
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create application letter', 500, $e->getMessage());
        }
    }

    public function show(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $application = ApplicationLetter::where('id_data_permohonan', $id)
                ->where('nip_pegawai', $user->username)
                ->first();

            if (!$application) {
                return $this->errorResponse('Application letter not found', 404);
            }

            return $this->successResponse(
                data: new ApplicationLetterResource($application),
                message: 'Application letter retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve application letter', 500, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $application = ApplicationLetter::where('id_data_permohonan', $id)
                ->where('nip_pegawai', $user->username)
                ->first();

            if (!$application) {
                return $this->errorResponse('Application letter not found', 404);
            }

            if ($application->status != 1) {
                return $this->errorResponse('Can only update pending applications', 403);
            }

            $validator = Validator::make($request->all(), [
                'jenis_permohonan' => 'sometimes|required|string|max:10',
                'tgl_mulai' => 'sometimes|required|date',
                'tgl_selesai' => 'sometimes|required|date|after_or_equal:tgl_mulai',
                'deskripsi' => 'sometimes|required|string',
                'alasan' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 422, $validator->errors()->toArray());
            }

            $application->update($request->only([
                'jenis_permohonan', 'tgl_mulai', 'tgl_selesai', 'deskripsi', 'alasan'
            ]));

            return $this->successResponse(
                data: new ApplicationLetterResource($application),
                message: 'Application letter updated successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update application letter', 500, $e->getMessage());
        }
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $application = ApplicationLetter::where('id_data_permohonan', $id)
                ->where('nip_pegawai', $user->username)
                ->first();

            if (!$application) {
                return $this->errorResponse('Application letter not found', 404);
            }

            if ($application->status != 1) {
                return $this->errorResponse('Can only delete pending applications', 403);
            }

            $application->delete();

            return $this->successResponse(
                data: null,
                message: 'Application letter deleted successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete application letter', 500, $e->getMessage());
        }
    }
}