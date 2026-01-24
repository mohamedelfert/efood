<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\TimeSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Support\Renderable;

class TimeScheduleController extends Controller
{
    public function __construct(
        private TimeSchedule $timeSchedule,
    ) {
    }

    /**
     * @return Renderable
     */
    public function timeScheduleIndex(): Renderable
    {
        $schedules = $this->timeSchedule->get();
        return view('admin-views.business-settings.time-schedule-index', compact('schedules'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function addSchedule(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_time' => 'required_unless:is_24_hours,1',
            'end_time' => 'required_unless:is_24_hours,1|after:start_time',
        ], [
            'end_time.after' => translate('End time must be after the start time')
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)]);
        }

        $is24Hours = $request->has('is_24_hours') && $request->is_24_hours == 1;
        $startTime = $is24Hours ? '00:00' : $request->start_time;
        $endTime = $is24Hours ? '23:59' : $request->end_time;

        $temp = $this->timeSchedule->where('day', $request->day)
            ->where(function ($q) use ($startTime, $endTime) {
                return $q->where(function ($query) use ($startTime) {
                    return $query->where('opening_time', '<=', $startTime)->where('closing_time', '>=', $startTime);
                })->orWhere(function ($query) use ($endTime) {
                    return $query->where('opening_time', '<=', $endTime)->where('closing_time', '>=', $endTime);
                });
            })
            ->first();

        if (isset($temp)) {
            return response()->json([
                'errors' => [
                    ['code' => 'time', 'message' => translate('schedule_overlapping_warning')]
                ]
            ]);
        }

        $this->timeSchedule->create([
            'day' => $request->day,
            'opening_time' => $startTime,
            'closing_time' => $endTime,
            'is_24_hours' => $is24Hours
        ]);

        $schedules = $this->timeSchedule->get();
        return response()->json(['view' => view('admin-views.business-settings.partials._schedule', compact('schedules'))->render()]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function removeSchedule(Request $request): JsonResponse
    {
        $schedule = $this->timeSchedule->find($request['schedule_id']);
        if (!$schedule) {
            return response()->json([], 404);
        }
        $restaurant = $schedule->restaurant;
        $schedule->delete();

        $schedules = $this->timeSchedule->get();
        return response()->json([
            'view' => view('admin-views.business-settings.partials._schedule', compact('schedules'))->render(),
        ]);
    }
}
