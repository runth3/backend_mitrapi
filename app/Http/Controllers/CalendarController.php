<?php

namespace App\Http\Controllers;

use App\Models\Calendar;
use App\Models\CalendarInstansi;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CalendarController extends Controller
{
    use ApiResponseTrait;

    public function getHolidays(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'id_instansi' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $date = $request->date;
            $idInstansi = $request->id_instansi;

            // Check if the date is a holiday for the user's instansi
            $holiday = Calendar::where(function ($q) use ($date) {
                $q->where('tgl_mulai', '<=', $date)
                  ->where('tgl_selesai', '>=', $date);
            })
            ->where('status', 'LIBUR')
            ->where(function ($q) use ($idInstansi) {
                $q->where('id_instansi', 'ALL')
                  ->orWhere('id_instansi', $idInstansi)
                  ->orWhereHas('calendarInstansi', function ($q2) use ($idInstansi) {
                      $q2->where('id_instansi', $idInstansi);
                  });
            })
            ->first();

            $isHoliday = $holiday !== null;

            return $this->successResponse(
                data: [
                    'date' => $date,
                    'is_holiday' => $isHoliday,
                    'holiday_info' => $holiday ? [
                        'id' => $holiday->id_data_kalender,
                        'title' => $holiday->judul,
                        'start_date' => $holiday->tgl_mulai->format('Y-m-d'),
                        'end_date' => $holiday->tgl_selesai->format('Y-m-d'),
                        'status' => $holiday->status,
                        'color' => $holiday->color
                    ] : null
                ],
                message: $isHoliday ? 'Date is a holiday' : 'Date is not a holiday'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check holiday', 500, $e->getMessage());
        }
    }

    public function getIncidentalDays(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return $this->errorResponse('User not authenticated', 401);
        }

        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'id_instansi' => 'required|string'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('Invalid input', 400, $validator->errors()->toArray());
            }

            $date = $request->date;
            $idInstansi = $request->id_instansi;

            // Check if the date is an incidental day (UPACARA) for the user's instansi
            $incidentalDay = Calendar::where(function ($q) use ($date) {
                $q->where('tgl_mulai', '<=', $date)
                  ->where('tgl_selesai', '>=', $date);
            })
            ->where('status', 'UPACARA')
            ->where(function ($q) use ($idInstansi) {
                $q->where('id_instansi', 'ALL')
                  ->orWhere('id_instansi', $idInstansi)
                  ->orWhereHas('calendarInstansi', function ($q2) use ($idInstansi) {
                      $q2->where('id_instansi', $idInstansi);
                  });
            })
            ->first();

            $isIncidentalDay = $incidentalDay !== null;

            return $this->successResponse(
                data: [
                    'date' => $date,
                    'is_incidental_day' => $isIncidentalDay,
                    'incidental_info' => $incidentalDay ? [
                        'id' => $incidentalDay->id_data_kalender,
                        'title' => $incidentalDay->judul,
                        'start_date' => $incidentalDay->tgl_mulai->format('Y-m-d'),
                        'end_date' => $incidentalDay->tgl_selesai->format('Y-m-d'),
                        'start_time' => $incidentalDay->jam_mulai,
                        'end_time' => $incidentalDay->jam_selesai,
                        'status' => $incidentalDay->status,
                        'color' => $incidentalDay->color
                    ] : null
                ],
                message: $isIncidentalDay ? 'Date is an incidental day' : 'Date is not an incidental day'
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to check incidental day', 500, $e->getMessage());
        }
    }
}