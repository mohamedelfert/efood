<?php

namespace App\Providers;

use App\CentralLogics\Helpers;
use App\Model\BusinessSetting;
use App\Model\Category;
use App\Models\LoginSetup;
use App\Observers\BusinessSettingObserver;
use App\Observers\CategoryObserver;
use App\Observers\LoginSetupObserver;
use App\Traits\SystemAddonTrait;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use App\Services\WhatsAppService;

ini_set('memory_limit', '-1');

class AppServiceProvider extends ServiceProvider
{
    use SystemAddonTrait;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WhatsAppService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Set default locale to Arabic if not already set in session
        if (!session()->has('local')) {
            session()->put('local', 'ar');
            session()->put('direction', 'rtl');
            app()->setLocale('ar');
        } else {
            $local = session('local');
            app()->setLocale($local);
            
            // Set direction based on language
            $language = Helpers::get_business_settings('language');
            if ($language) {
                foreach ($language as $lang) {
                    if ($lang['code'] == $local) {
                        session()->put('direction', $lang['direction'] ?? 'ltr');
                        break;
                    }
                }
            }
        }

        BusinessSetting::observe(BusinessSettingObserver::class);
        LoginSetup::observe(LoginSetupObserver::class);
        Category::observe(CategoryObserver::class);

        //for system addon
        Config::set('addon_admin_routes',$this->get_addon_admin_routes());
        Config::set('get_payment_publish_status',$this->get_payment_publish_status());

        try {
            $timezone = BusinessSetting::where(['key' => 'time_zone'])->first();
            if (isset($timezone)) {
                config(['app.timezone' => $timezone->value]);
                date_default_timezone_set($timezone->value);
            }
        }catch(\Exception $exception){}

        Paginator::useBootstrap();

        // --------------------------------------
        // Force HTTPS for all URLs
        // --------------------------------------
        if(config('app.env') !== 'local') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if(env('APP_ENV') !== 'local') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
