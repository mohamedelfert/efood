<?php

namespace App\Http\Controllers\Admin;

use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Contracts\Support\Renderable;

class DatabaseSettingsController extends Controller
{
    /**
     * @return Renderable
     */
    public function databaseIndex(): Renderable
    {
        $tables = DB::connection()->getDoctrineSchemaManager()->listTableNames();
        $filterTables = array('admins', 'branches', 'business_settings', 'email_verifications', 'failed_jobs', 'migrations', 'oauth_access_tokens', 'oauth_auth_codes', 'oauth_clients', 'oauth_personal_access_clients', 'oauth_refresh_tokens', 'password_resets', 'phone_verifications', 'soft_credentials', 'users', 'currencies', 'admin_roles');
        $tables = array_values(array_diff($tables, $filterTables));

        $rows = [];
        foreach ($tables as $table) {
            $count = DB::table($table)->count();
            $rows[] = $count;
        }

        return view('admin-views.business-settings.db-index', compact('tables', 'rows'));
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function cleanDatabase(Request $request): RedirectResponse
    {
        $tables = (array)$request->tables;

        if(count($tables) == 0) {
            Toastr::error(translate('No Table Updated'));
            return back();
        }

        try {
            DB::transaction(function () use ($tables) {
                foreach ($tables as $table) {
                    DB::table($table)->delete();
                }
            });
        } catch (\Exception $exception) {
            Toastr::error(translate('Failed to update!'));
            return back();
        }

        Toastr::success(translate('Updated successfully!'));
        return back();
    }

    /**
     * Show the database backup page
     */
    public function backupIndex(): Renderable
    {
        // You can later pass backup list here
        return view('admin-views.business-settings.db-backup');
    }

    /**
     * Trigger manual database backup
     */
    public function backupDatabase(): RedirectResponse
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);

            Toastr::success(translate('Database backup created successfully!'));
        } catch (\Exception $e) {
            Toastr::error(translate('Backup failed: ') . $e->getMessage());
        }

        return back();
    }
}
