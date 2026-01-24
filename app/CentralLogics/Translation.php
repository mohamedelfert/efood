<?php

namespace App\CentralLogics {

    use Illuminate\Support\Facades\App;

    if (!function_exists('App\CentralLogics\translate')) {
        function translate($key)
        {
            $local = session()->has('local') ? session('local') : 'en';
            App::setLocale($local);

            $langFile = base_path("resources/lang/{$local}/messages.php");
            $lang_array = file_exists($langFile) ? include($langFile) : [];

            $processed_key = ucfirst(str_replace('_', ' ', \App\CentralLogics\Helpers::remove_invalid_charcaters($key)));

            if (!array_key_exists($key, $lang_array)) {
                $result = $processed_key;
            } else {
                $result = __('messages.' . $key);
            }

            return $result;
        }
    }

    if (!function_exists('App\CentralLogics\auth_branch')) {
        function auth_branch()
        {
            if (auth('branch')->check()) {
                return auth('branch')->user();
            }
            return auth('admin')->user();
        }
    }

    if (!function_exists('App\CentralLogics\auth_branch_id')) {
        function auth_branch_id()
        {
            if (auth('branch')->check()) {
                return auth('branch')->id();
            }
            return auth('admin')->user()->branch_id ?? null;
        }
    }
}

// Global namespace wrappers for backward compatibility
namespace {
    if (!function_exists('translate')) {
        function translate($key)
        {
            return \App\CentralLogics\translate($key);
        }
    }

    if (!function_exists('auth_branch')) {
        function auth_branch()
        {
            return \App\CentralLogics\auth_branch();
        }
    }

    if (!function_exists('auth_branch_id')) {
        function auth_branch_id()
        {
            return \App\CentralLogics\auth_branch_id();
        }
    }
}
