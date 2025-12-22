<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\PaymobController;
use App\Http\Controllers\FirebaseController;
use App\Http\Controllers\PaystackController;
use App\Http\Controllers\RazorPayController;
use App\Http\Controllers\SenangPayController;
use App\Http\Controllers\FlutterwaveController;
use App\Http\Controllers\BkashPaymentController;
use App\Http\Controllers\PaypalPaymentController;
use App\Http\Controllers\StripePaymentController;
use App\Http\Controllers\SslCommerzPaymentController;

/**
 * Admin login
 */
Route::get('/', function () {
    return redirect(\route('admin.dashboard'));
});

Route::post('/subscribeToTopic', [FirebaseController::class, 'subscribeToTopic']);

/**
 * Pages
 */
Route::get('about-us', 'HomeController@about_us')->name('about-us');
Route::get('terms-and-conditions', 'HomeController@terms_and_conditions')->name('terms-and-conditions');
Route::get('privacy-policy', 'HomeController@privacy_policy')->name('privacy-policy');

/**
 * Auth
 */
Route::get('authentication-failed', function () {
    $errors = [];
    array_push($errors, ['code' => 'auth-001', 'message' => 'Unauthenticated.']);
    return response()->json([
        'errors' => $errors,
    ], 401);
})->name('authentication-failed');

/**
 * Payment
 */
Route::group(['prefix' => 'payment-mobile'], function () {
    Route::get('/', 'PaymentController@payment')->name('payment-mobile');
    Route::get('set-payment-method/{name}', 'PaymentController@set_payment_method')->name('set-payment-method');
});

Route::get('payment-success', 'PaymentController@success')->name('payment-success');
Route::get('payment-fail', 'PaymentController@fail')->name('payment-fail');

$is_published = 0;
try {
    $full_data = include('Modules/Gateways/Addon/info.php');
    $is_published = $full_data['is_published'] == 1 ? 1 : 0;
} catch (\Exception $exception) {}

if (!$is_published) {
    Route::group(['prefix' => 'payment'], function () {

        //PAYMOB
        Route::group(['prefix' => 'paymob', 'as' => 'paymob.'], function () {
            Route::any('pay', [PaymobController::class, 'credit'])->name('pay');
            Route::any('callback', [PaymobController::class, 'callback'])->name('callback');
        });
    });
}



/**
 * Currency
 */
Route::get('add-currency', function () {
    $currencies = file_get_contents("installation/currency.json");
    $decoded = json_decode($currencies, true);
    $keep = [];
    foreach ($decoded as $item) {
        $keep[] = [
            'country' => $item['name'],
            'code' => $item['code'],
            'symbol' => $item['symbol_native'],
            'exchange_rate' => 1,
        ];
    }
    DB::table('currencies')->insert($keep);
    return response()->json(['ok']);
});

Route::get('setup-currencies', function () {
    $currencies = GATEWAYS_CURRENCIES;
    $added = 0;
    
    foreach ($currencies as $currency) {
        $exists = \App\Model\Currency::where('code', $currency['code'])->exists();
        
        if (!$exists) {
            \App\Model\Currency::create([
                'name' => $currency['name'],
                'code' => $currency['code'],
                'symbol' => $currency['symbol'],
                'exchange_rate' => $currency['code'] === 'USD' ? 1.0000 : 1.0000,
                'is_primary' => $currency['code'] === 'USD',
                'is_active' => $currency['code'] === 'USD',
                'decimal_places' => 2,
                'position' => 'before',
            ]);
            $added++;
        }
    }
    
    return "تم إضافة $added عملة بنجاح";
});

Route::get('test', function () {
    Mail::to('abdurrahman.6amtech@gmail.com')->send(new \App\Mail\OrderPlaced(100628));
});

Route::get('/storage/qr_codes/{filename}', function ($filename) {
    $path = 'public/qr_codes/' . $filename;

    if (!Storage::exists($path)) {
        abort(404);
    }

    return response()->file(Storage::path($path));
})->where('filename', 'qr_[0-9]+_[0-9]+\.png');
