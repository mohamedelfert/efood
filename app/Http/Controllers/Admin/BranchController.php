<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use App\Model\TimeSchedule;
use App\Models\DeliveryChargeByArea;
use App\Models\DeliveryChargeSetup;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchController extends Controller
{
    public function __construct(
        private Branch $branch,
        private DeliveryChargeSetup $deliveryChargeSetup,
        private DeliveryChargeByArea $deliveryChargeByArea,
        private TimeSchedule $timeSchedule
    )
    {}

    /**
     * @return Renderable
     */
    public function index(): Renderable
    {
        return view('admin-views.branch.index');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|max:255|unique:branches',
            'email' => 'required|max:255|unique:branches',
            'password' => 'required|min:8|max:255',
            'preparation_time' => 'required',
            'image' => 'nullable|max:2048',
            'schedule' => 'required|array',
            'schedule.*' => 'required|array',
        ], [
            'name.required' => translate('Name is required!'),
            'schedule.required' => translate('Schedule is required!'),
        ]);

        DB::beginTransaction();
        try {
            // Upload images
            if (!empty($request->file('image'))) {
                $imageName = Helpers::upload('branch/', 'png', $request->file('image'));
            } else {
                $imageName = 'def.png';
            }

            if (!empty($request->file('cover_image'))) {
                $coverImageName = Helpers::upload('branch/', 'png', $request->file('cover_image'));
            } else {
                $coverImageName = 'def.png';
            }

            // Get default values from branch 1
            $defaultBranch = $this->branch->find(1);
            $defaultLat = $defaultBranch->latitude ?? '23.777176';
            $defaultLong = $defaultBranch->longitude ?? '90.399452';
            $defaultCoverage = $defaultBranch->coverage ?? 100;

            // Create branch
            $branch = $this->branch;
            $branch->name = $request->name;
            $branch->email = $request->email;
            $branch->latitude = $request->latitude ?? $defaultLat;
            $branch->longitude = $request->longitude ?? $defaultLong;
            $branch->coverage = $request->coverage ?? $defaultCoverage;
            $branch->address = $request->address;
            $branch->phone = $request->phone ?? null;
            $branch->password = bcrypt($request->password);
            $branch->preparation_time = $request->preparation_time;
            $branch->image = $imageName;
            $branch->cover_image = $coverImageName;
            $branch->save();

            // Create delivery charge setup
            $branchDeliveryCharge = $this->deliveryChargeSetup;
            $branchDeliveryCharge->branch_id = $branch->id;
            $branchDeliveryCharge->delivery_charge_type = 'fixed';
            $branchDeliveryCharge->fixed_delivery_charge = 0;
            $branchDeliveryCharge->save();

            // Create time schedules
            $schedules = $request->schedule;
            foreach ($schedules as $day => $daySchedules) {
                foreach ($daySchedules as $schedule) {
                    $is24Hours = isset($schedule['is_24_hours']) && $schedule['is_24_hours'] == 1;
                    
                    $this->timeSchedule->create([
                        'branch_id' => $branch->id,
                        'day' => $day,
                        'opening_time' => $is24Hours ? '00:00' : $schedule['start_time'],
                        'closing_time' => $is24Hours ? '23:59' : $schedule['end_time'],
                        'is_24_hours' => $is24Hours,
                    ]);
                }
            }

            DB::commit();
            Toastr::success(translate('Branch created successfully with schedules!'));
            return redirect()->route('admin.branch.list');

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to create branch: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * Get schedule for a branch (AJAX)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSchedule(Request $request): JsonResponse
    {
        $branchId = $request->branch_id;
        
        if (!$branchId) {
            return response()->json(['success' => false, 'message' => '{{translate("Branch ID is required")}}'], 400);
        }

        $schedules = $this->timeSchedule
            ->where('branch_id', $branchId)
            ->orderBy('day')
            ->orderBy('opening_time')
            ->get();

        return response()->json([
            'success' => true,
            'schedules' => $schedules
        ]);
    }

    /**
     * @param $id
     * @return Renderable
     */
    public function edit($id): Renderable
    {
        $branch = $this->branch->with('timeSchedules')->find($id);
        
        // Group schedules by day
        $schedulesByDay = $branch->timeSchedules->groupBy('day');
        
        return view('admin-views.branch.edit', compact('branch', 'schedulesByDay'));
    }

    /**
     * @param Request $request
     * @param $id
     * @return RedirectResponse
     */
    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|max:255',
            'preparation_time' => 'required',
            'email' => ['required', 'unique:branches,email,' . $id . ',id'],
            'image' => 'max:2048',
            'password' => 'nullable|min:8|max:255',
            'schedule' => 'nullable|array',
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        DB::beginTransaction();
        try {
            $branch = $this->branch->find($id);
            $branch->name = $request->name;
            $branch->email = $request->email;
            $branch->longitude = $request->longitude ? $request->longitude : $branch->longitude;
            $branch->latitude = $request->latitude ? $request->latitude : $branch->latitude;
            $branch->coverage = $request->coverage ? $request->coverage : $branch->coverage;
            $branch->address = $request->address;
            $branch->image = $request->has('image') ? Helpers::update('branch/', $branch->image, 'png', $request->file('image')) : $branch->image;
            $branch->cover_image = $request->has('cover_image') ? Helpers::update('branch/', $branch->cover_image, 'png', $request->file('cover_image')) : $branch->cover_image;
            
            if ($request['password'] != null) {
                $branch->password = bcrypt($request->password);
            }
            
            $branch->phone = $request->phone ?? '';
            $branch->preparation_time = $request->preparation_time;
            $branch->save();

            // Update schedules if provided
            if ($request->has('schedule')) {
                // Delete existing schedules
                $this->timeSchedule->where('branch_id', $id)->delete();

                // Create new schedules
                $schedules = $request->schedule;
                foreach ($schedules as $day => $daySchedules) {
                    foreach ($daySchedules as $schedule) {
                        $is24Hours = isset($schedule['is_24_hours']) && $schedule['is_24_hours'] == 1;
                        
                        $this->timeSchedule->create([
                            'branch_id' => $branch->id,
                            'day' => $day,
                            'opening_time' => $is24Hours ? '00:00' : $schedule['start_time'],
                            'closing_time' => $is24Hours ? '23:59' : $schedule['end_time'],
                            'is_24_hours' => $is24Hours,
                        ]);
                    }
                }
            }

            DB::commit();
            Toastr::success(translate('Branch updated successfully!'));
            return back();

        } catch (\Exception $e) {
            DB::rollBack();
            Toastr::error(translate('Failed to update branch: ') . $e->getMessage());
            return back()->withInput();
        }
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $branch = $this->branch->find($request->id);

        if ($branch && $branch->delete()) {
            $this->deliveryChargeSetup->where(['branch_id' => $request->id])->delete();
            $this->deliveryChargeByArea->where(['branch_id' => $request->id])->delete();
            $this->timeSchedule->where(['branch_id' => $request->id])->delete();

            Toastr::success(translate('Branch removed along with related data!'));
        } else {
            Toastr::error(translate('Failed to remove branch!'));
        }
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $branch = $this->branch->find($request->id);
        $branch->status = $request->status;
        $branch->save();

        Toastr::success(translate('Branch status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function list(Request $request): Renderable
    {
        $search = $request['search'];
        $query = $this->branch
            ->with('delivery_charge_setup')
            ->when($search, function ($q) use ($search) {
                $key = explode(' ', $search);
                foreach ($key as $value) {
                    $q->orWhere('id', 'like', "%{$value}%")
                        ->orWhere('name', 'like', "%{$value}%");
                }
            });

        $queryParam = ['search' => $request['search']];
        $branches = $query->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($queryParam);

        return view('admin-views.branch.list', compact('branches', 'search'));
    }
}