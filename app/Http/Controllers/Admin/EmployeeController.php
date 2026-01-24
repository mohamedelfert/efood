<?php

namespace App\Http\Controllers\Admin;

use App\Model\Admin;
use App\Model\Branch;
use App\Model\AdminRole;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Http\RedirectResponse;
use Box\Spout\Common\Exception\IOException;
use Illuminate\Contracts\Support\Renderable;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Box\Spout\Common\Exception\InvalidArgumentException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Writer\Exception\WriterNotOpenedException;


class EmployeeController extends Controller
{
    public function __construct(
        private Admin $admin,
        private AdminRole $admin_role
    ) {
    }

    // دالة لجلب الفروع المتاحة (الجديدة)
    private function getAvailableBranches()
    {
        $user = auth('admin')->user();

        if ($user->admin_role_id == 1) {
            return Branch::active()->get();
        }

        return Branch::where('id', $user->branch_id)->get();
    }

    public function index(): Renderable
    {
        $roles = $this->getAvailableRoles();
        $branches = $this->getAvailableBranches();

        return view('admin-views.employee.add-new', compact('roles', 'branches'));
    }

    public function store(Request $request): RedirectResponse
    {
        $rules = [
            'name' => 'required',
            'role_id' => 'required|exists:admin_roles,id',
            'branch_id' => 'required',
            'email' => 'required|email|unique:admins,email',
            'password' => 'required|min:8',
            'phone' => 'required',
        ];

        $messages = [
            'name.required' => translate('Name is required!'),
            'role_id.required' => translate('Role is required!'),
            'role_id.exists' => translate('Selected role is invalid!'),
            'branch_id.required' => translate('Branch is required!'),
            'email.required' => translate('Email is required!'),
            'email.email' => translate('Invalid email address!'),
            'email.unique' => translate('Email is already taken!'),
            'password.required' => translate('Password is required!'),
            'password.min' => translate('Password must be at least 8 characters!'),
            'phone.required' => translate('Phone is required!'),
        ];

        $request->validate($rules, $messages);

        $branch_id = ($request->branch_id == 0 || $request->branch_id == null) ? null : $request->branch_id;

        $role = $this->admin_role->findOrFail($request->role_id);

        if ($role->id == 1) {
            Toastr::warning(translate('Access Denied!'));
            return back();
        }

        $user = auth('admin')->user();

        // تقييد مدير الفرع: لا يمكنه إضافة موظف في فرع آخر
        if ($user->admin_role_id != 1 && $branch_id != $user->branch_id) {
            Toastr::error(translate('You are not allowed to add employee in another branch!'));
            return back();
        }

        $this->admin->create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'admin_role_id' => $request->role_id,
            'branch_id' => $branch_id,
            'password' => bcrypt($request->password),
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Toastr::success(translate('Employee added successfully!'));
        return redirect()->route('admin.employee.list');
    }

    public function list(Request $request): Renderable
    {
        $search = $request->get('search');

        $query = $this->admin->with(['role', 'branch'])
            ->whereNotIn('id', [1]);

        $user = auth('admin')->user();

        if ($user->admin_role_id != 1) {
            $query->where('branch_id', $user->branch_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $employees = $query->latest()->paginate(Helpers::getPagination());

        return view('admin-views.employee.list', compact('employees', 'search'));
    }

    public function edit($id): Renderable
    {
        $employee = $this->admin->findOrFail($id);

        $user = auth('admin')->user();

        if ($user->admin_role_id != 1 && $employee->branch_id != $user->branch_id) {
            abort(403);
        }

        $roles = $this->getAvailableRoles();
        $branches = $this->getAvailableBranches();

        return view('admin-views.employee.edit', compact('employee', 'roles', 'branches'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $employee = $this->admin->findOrFail($id);

        $user = auth('admin')->user();

        if ($user->admin_role_id != 1 && $employee->branch_id != $user->branch_id) {
            abort(403);
        }

        $rules = [
            'name' => 'required',
            'role_id' => 'required|exists:admin_roles,id',
            'branch_id' => 'required',
            'email' => 'required|email|unique:admins,email,' . $id,
            'phone' => 'required',
        ];

        $messages = [
            'name.required' => translate('Name is required!'),
            'role_id.required' => translate('Role is required!'),
            'role_id.exists' => translate('Selected role is invalid!'),
            'branch_id.required' => translate('Branch is required!'),
            'email.required' => translate('Email is required!'),
            'email.email' => translate('Invalid email address!'),
            'email.unique' => translate('Email is already taken!'),
            'phone.required' => translate('Phone is required!'),
        ];

        $request->validate($rules, $messages);

        $branch_id = ($request->branch_id == 0 || $request->branch_id == null) ? null : $request->branch_id;

        if ($user->admin_role_id != 1 && $branch_id != $user->branch_id) {
            Toastr::error(translate('You are not allowed to change branch!'));
            return back();
        }

        $password = $request->filled('password') ? bcrypt($request->password) : $employee->password;

        $employee->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'admin_role_id' => $request->role_id,
            'branch_id' => $branch_id,
            'password' => $password,
            'updated_at' => now(),
        ]);

        Toastr::success(translate('Employee updated successfully!'));
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function status(Request $request): RedirectResponse
    {
        $employee = $this->admin->find($request->id);
        $employee->status = $request->status;
        $employee->save();

        Toastr::success(translate('Employee status updated!'));
        return back();
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function delete(Request $request): RedirectResponse
    {
        if ($request->id == 1) {
            Toastr::warning(translate('Master_Admin_can_not_be_deleted'));

        } else {
            $action = $this->admin->destroy($request->id);
            if ($action) {
                Toastr::success(translate('employee_deleted_successfully'));
            } else {
                Toastr::error(translate('employee_is_not_deleted'));
            }
        }
        return back();
    }

    /**
     * @return string|StreamedResponse
     * @throws IOException
     * @throws InvalidArgumentException
     * @throws UnsupportedTypeException
     * @throws WriterNotOpenedException
     */
    public function exportExcel(): StreamedResponse|string
    {
        $employees = $this->admin
            ->whereNotIn('id', [1])
            ->get(['id', 'name', 'email', 'admin_role_id', 'status']);

        return (new FastExcel($employees))->download('employees.xlsx');
    }

    private function getAvailableRoles()
    {
        $user = auth('admin')->user();

        // Master Admin (role_id = 1) يرى كل الأدوار عدا نفسه
        if ($user->admin_role_id == 1) {
            return $this->admin_role->whereNotIn('id', [1])->get();
        }

        // مدير فرع يرى فقط أدوار فرعه
        return $this->admin_role
            ->where('branch_id', $user->branch_id)
            ->whereNotIn('id', [1])
            ->get();
    }

    private function handleIdentityImages($request)
    {
        if ($request->hasFile('identity_image')) {
            return json_encode(array_map(function ($img) {
                return Helpers::upload('admin/', 'png', $img);
            }, $request->file('identity_image')));
        }
        return json_encode([]);
    }
}