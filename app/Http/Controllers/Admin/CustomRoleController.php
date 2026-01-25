<?php

namespace App\Http\Controllers\Admin;

use App\Model\Admin;
use App\Model\Branch;
use App\Model\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Renderable;

class CustomRoleController extends Controller
{
    public function __construct(
        private AdminRole $adminRole,
        private Admin $admin
    ) {
    }

    public function create(Request $request): Renderable
    {
        // تعريف $search دائمًا، حتى لو كان فارغًا
        $search = $request->get('search', '');

        $user = auth('admin')->user();

        $query = $this->adminRole->with('branch')->whereNotIn('id', [1]);

        // إذا لم يكن Master Admin → فلترة حسب الفرع + الأدوار المتاحة لكل الفروع
        if ($user->admin_role_id != 1) {
            $query->where(function ($q) use ($user) {
                $q->where('branch_id', $user->branch_id)
                    ->orWhere('all_branches', 1);
            });
        }

        // تطبيق البحث إذا وجد
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach (explode(' ', $search) as $term) {
                    $q->where('name', 'like', "%{$term}%");
                }
            });
        }

        $roles = $query->latest()->get();

        $branches = Branch::active()->get();

        // الآن $search معرف دائمًا
        return view('admin-views.custom-role.create', compact('roles', 'search', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|unique:admin_roles,name',
            'modules' => 'required|array|min:1',
            'branch_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value != '0' && !Branch::where('id', $value)->exists()) {
                        $fail(translate('Branch ID does not exist'));
                    }
                }
            ],
        ]);

        $user = auth('admin')->user();

        $branchId = $request->branch_id;

        // مدير الفرع لا يمكنه اختيار فرع آخر
        if ($user->admin_role_id != 1 && $branchId != $user->branch_id) {
            Toastr::error('غير مسموح لك بإنشاء دور لفرع آخر!');
            return back();
        }

        // أو اجبره على فرعه تلقائيًا
        if ($user->admin_role_id != 1) {
            $branchId = $user->branch_id;
        }

        $this->adminRole->create([
            'name' => $request->name,
            'branch_id' => $request->branch_id == '0' ? null : $branchId,
            'all_branches' => $request->branch_id == '0' ? 1 : 0,
            'module_access' => json_encode($request->modules),
            'status' => 1,
        ]);

        Toastr::success(translate('Role added successfully!'));
        return back();
    }

    public function edit($id): Renderable
    {
        $role = $this->adminRole->with('branch')->findOrFail($id);
        $branches = Branch::active()->get();

        return view('admin-views.custom-role.edit', compact('role', 'branches'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required',
            'branch_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if ($value != '0' && !Branch::where('id', $value)->exists()) {
                        $fail(translate('Branch ID does not exist'));
                    }
                }
            ],
            'modules' => 'required|array|min:1',
        ]);

        $this->adminRole->findOrFail($id)->update([
            'name' => $request->name,
            'branch_id' => $request->branch_id == '0' ? null : $request->branch_id,
            'all_branches' => $request->branch_id == '0' ? 1 : 0,
            'module_access' => json_encode($request->modules),
        ]);

        Toastr::success(translate('Role updated successfully!'));
        return redirect()->route('admin.custom-role.create');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        $roleExist = $this->admin->where('admin_role_id', $request->id)->first();
        if ($roleExist) {
            Toastr::warning(translate('employee_assigned_on_this_role._Delete_failed'));
        } else {
            $action = $this->adminRole->destroy($request->id);
            if ($action) {
                Toastr::success(translate('role_deleted_sucessfully'));
            } else {
                Toastr::warning(translate('delete_failed'));
            }
        }

        return back();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\StreamedResponse|string
     * @throws \Box\Spout\Common\Exception\IOException
     * @throws \Box\Spout\Common\Exception\InvalidArgumentException
     * @throws \Box\Spout\Common\Exception\UnsupportedTypeException
     * @throws \Box\Spout\Writer\Exception\WriterNotOpenedException
     */
    public function excelExport(): \Symfony\Component\HttpFoundation\StreamedResponse|string
    {
        $roles = $this->adminRole->select('id', 'name', 'module_access', 'status')->get();
        return (new FastExcel($roles))->download('employee_role.xlsx');
    }

    /**
     * @param $id
     * @param Request $request
     * @return JsonResponse
     */
    public function changeStatus($id, Request $request): JsonResponse
    {
        $roleExist = $this->admin->where('admin_role_id', $id)->first();

        if ($roleExist) {
            return response()->json(translate('employee_assigned_on_this_role._Update_failed'), 409);
        } else {
            $action = $this->adminRole->where('id', $id)->update(['status' => $request['status']]);
            if ($action) {
                return response()->json(translate('status_changed_successfully'), 200);
            } else {
                return response()->json(translate('status_update_failed'), 500);
            }
        }
    }
}
