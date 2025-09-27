<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Model\Currency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CurrencyController extends Controller
{
    public function index()
    {
        $currencies = Currency::orderBy('is_primary', 'desc')
                            ->orderBy('name')
                            ->paginate(15);
        
        return view('admin-views.currency.index', compact('currencies'));
    }

    public function create()
    {
        $availableCurrencies = $this->getAvailableCurrencies();
        return view('admin-views.currency.create', compact('availableCurrencies'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_code' => 'required|string|size:3|unique:currencies,code',
            'exchange_rate' => 'required|numeric|min:0.0001',
            'decimal_places' => 'required|integer|min:0|max:4',
            'position' => 'required|in:before,after'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $currencyData = $this->getCurrencyFromConstant($request->currency_code);
        
        if (!$currencyData) {
            return back()->withErrors(['currency_code' => 'رمز عملة غير صالح'])->withInput();
        }

        if ($request->is_primary) {
            Currency::where('is_primary', true)->update(['is_primary' => false]);
        }

        Currency::create([
            'name' => $currencyData['name'],
            'code' => $currencyData['code'],
            'symbol' => $currencyData['symbol'],
            'exchange_rate' => $request->exchange_rate,
            'is_primary' => $request->boolean('is_primary'),
            'is_active' => $request->boolean('is_active', true),
            'decimal_places' => $request->decimal_places,
            'position' => $request->position
        ]);

        return redirect()->route('admin.currency.index')
                        ->with('success', 'تم إضافة العملة بنجاح');
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);
        return view('admin-views.currency.edit', compact('currency'));
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'exchange_rate' => 'required|numeric|min:0.0001',
            'decimal_places' => 'required|integer|min:0|max:4',
            'position' => 'required|in:before,after'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($request->is_primary && !$currency->is_primary) {
            Currency::where('is_primary', true)->update(['is_primary' => false]);
        }

        $currency->update([
            'exchange_rate' => $request->exchange_rate,
            'is_primary' => $request->boolean('is_primary'),
            'is_active' => $request->boolean('is_active'),
            'decimal_places' => $request->decimal_places,
            'position' => $request->position
        ]);

        return redirect()->route('admin.currency.index')
                        ->with('success', 'تم تحديث العملة بنجاح');
    }

    public function setPrimary($id)
    {
        $currency = Currency::findOrFail($id);
        
        Currency::where('is_primary', true)->update(['is_primary' => false]);
        
        $currency->update([
            'is_primary' => true, 
            'is_active' => true,
            'exchange_rate' => 1.0000
        ]);
        
        return redirect()->back()->with('success', 'تم تحديث العملة الأساسية بنجاح');
    }

    public function toggleStatus($id)
    {
        $currency = Currency::findOrFail($id);
        
        if ($currency->is_primary && $currency->is_active) {
            return redirect()->back()->with('error', 'لا يمكن إلغاء تفعيل العملة الأساسية');
        }
        
        $currency->update(['is_active' => !$currency->is_active]);
        
        return redirect()->back()->with('success', 'تم تحديث حالة العملة');
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);
        
        if ($currency->is_primary) {
            return redirect()->back()->with('error', 'لا يمكن حذف العملة الأساسية');
        }
        
        $currency->delete();
        
        return redirect()->back()->with('success', 'تم حذف العملة بنجاح');
    }

    public function updateExchangeRates()
    {
        $currencies = Currency::where('is_active', true)->get();
        return view('admin-views.currency.exchange-rates', compact('currencies'));
    }

    private function getAvailableCurrencies()
    {
        $existingCodes = Currency::pluck('code')->toArray();
        $availableCurrencies = [];
        
        foreach (GATEWAYS_CURRENCIES as $currency) {
            if (!in_array($currency['code'], $existingCodes)) {
                $availableCurrencies[] = $currency;
            }
        }
        
        usort($availableCurrencies, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });
        
        return $availableCurrencies;
    }

    private function getCurrencyFromConstant($code)
    {
        foreach (GATEWAYS_CURRENCIES as $currency) {
            if ($currency['code'] === $code) {
                return $currency;
            }
        }
        return null;
    }

    public function saveExchangeRates(Request $request)
    {
        $rates = $request->exchange_rates ?? [];
        $updated = 0;
        
        foreach ($rates as $currencyId => $rate) {
            $currency = Currency::find($currencyId);
            if ($currency && !$currency->is_primary && $rate > 0) {
                $currency->update(['exchange_rate' => $rate]);
                $updated++;
            }
        }
        
        return redirect()->back()->with('success', translate('Updated') . ' ' . $updated . ' ' . translate('exchange rates successfully'));
    }
}