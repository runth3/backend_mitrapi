<?php

namespace App\Http\Controllers;

use App\Models\ApplicationLetter;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
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
}