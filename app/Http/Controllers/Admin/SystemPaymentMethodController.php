<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\SystemPaymentMethod;
use App\Services\PaymentGatewayHelper;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SystemPaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;
        $paymentMethods = SystemPaymentMethod::query()
            ->when($search, function ($q) use ($search) {
                $q->where('method_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(Helpers::getPagination());

        return view('admin-views.system-payment-method.index', compact('paymentMethods', 'search'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'method_name' => 'required|string|max:255',
            'driver_name' => 'required|string',
            'mode' => 'required|in:live,test',
        ]);

        $settings = [];
        if ($request->has('keys') && $request->has('values')) {
            foreach ($request->keys as $index => $key) {
                if (!empty($key)) {
                    $settings[$key] = $request->values[$index] ?? null;
                }
            }
        }

        $slug = Str::slug($request->driver_name);
        if (SystemPaymentMethod::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . time();
        }

        $method = new SystemPaymentMethod();
        $method->method_name = $request->method_name;
        $method->slug = $slug;
        $method->driver_name = $request->driver_name;
        $method->mode = $request->mode;
        $method->settings = $settings;
        $method->is_active = true;

        if ($request->hasFile('gateway_image')) {
            $method->image = Helpers::upload('payment_modules/gateway_image/', 'png', $request->file('gateway_image'));
        }

        $method->save();

        Toastr::success(translate('Payment method added successfully'));
        return redirect()->route('admin.business-settings.web-app.payment-method');
    }


    public function update(Request $request, $id)
    {
        $method = SystemPaymentMethod::findOrFail($id);

        $request->validate([
            'method_name' => 'required|string|max:255',
            'mode' => 'required|in:live,test',
        ]);

        $settings = [];
        if ($request->has('keys') && $request->has('values')) {
            foreach ($request->keys as $index => $key) {
                if (!empty($key)) {
                    $settings[$key] = $request->values[$index] ?? null;
                }
            }
        }

        $method->method_name = $request->method_name;
        $method->mode = $request->mode;
        // Replace old settings with new ones completely
        $method->settings = $settings;

        if ($request->hasFile('gateway_image')) {
            $method->image = Helpers::upload('payment_modules/gateway_image/', 'png', $request->file('gateway_image'));
        }

        $method->save();

        Toastr::success(translate('Payment method updated successfully'));
        return redirect()->route('admin.business-settings.web-app.payment-method');
    }

    public function delete(Request $request)
    {
        $method = SystemPaymentMethod::findOrFail($request->id);
        $method->delete();
        Toastr::success(translate('Payment method deleted successfully'));
        return back();
    }

    public function status($id, $status)
    {
        $method = SystemPaymentMethod::findOrFail($id);
        $method->is_active = $status;
        $method->save();
        Toastr::success(translate('Status updated successfully'));
        return back();
    }
}
