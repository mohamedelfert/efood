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
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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
     * Show the database backup page with list of existing backups
     */
    public function backupIndex(): Renderable
    {
        $backups = $this->getBackupList();
        return view('admin-views.business-settings.db-backup', compact('backups'));
    }

    /**
     * Trigger manual database backup
     */
    public function backupDatabase(): RedirectResponse
    {
        try {
            // Check if using Spatie package or custom backup
            if (class_exists('\Spatie\Backup\Commands\BackupCommand')) {
                // Using Spatie Laravel Backup
                Artisan::call('backup:run', ['--only-db' => true]);
            } else {
                // Use custom backup method
                $this->createCustomBackup();
            }

            Toastr::success(translate('Database backup created successfully!'));
        } catch (\Exception $e) {
            Toastr::error(translate('Backup failed: ') . $e->getMessage());
        }

        return back();
    }

    /**
     * Get list of all backup files
     */
    private function getBackupList(): array
    {
        $backupPath = storage_path('app/backups');
        $backups = [];

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
            return $backups;
        }

        $files = File::files($backupPath);

        foreach ($files as $file) {
            $backups[] = [
                'name' => $file->getFilename(),
                'size' => $this->formatBytes($file->getSize()),
                'date' => date('Y-m-d H:i:s', $file->getMTime()),
                'path' => $file->getPathname(),
            ];
        }

        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }

    /**
     * Custom backup method (without Spatie package)
     */
    private function createCustomBackup(): void
    {
        $filename = 'backup-' . date('Y-m-d-H-i-s') . '.sql';
        $path = storage_path('app/backups');
        
        if (!File::exists($path)) {
            File::makeDirectory($path, 0755, true);
        }

        $fullPath = $path . '/' . $filename;

        // Get database credentials
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port', 3306);

        // Create backup using mysqldump
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > "%s"',
                $host,
                $port,
                $username,
                $password,
                $database,
                $fullPath
            );
        } else {
            // Linux/Unix
            $command = sprintf(
                'mysqldump -h%s -P%s -u%s -p%s %s > %s 2>&1',
                $host,
                $port,
                $username,
                $password,
                $database,
                $fullPath
            );
        }

        exec($command, $output, $return);

        if ($return !== 0) {
            throw new \Exception('Backup command failed');
        }

        // Clean old backups (keep only last 10 backups)
        $this->cleanOldBackups($path, 10);
    }

    /**
     * Clean old backup files
     */
    private function cleanOldBackups(string $path, int $keep = 10): void
    {
        $files = collect(File::files($path))
            ->sortByDesc(function ($file) {
                return $file->getMTime();
            })
            ->values();

        if ($files->count() > $keep) {
            $files->slice($keep)->each(function ($file) {
                File::delete($file->getPathname());
            });
        }
    }

    /**
     * Download a backup file
     */
    public function downloadBackup(string $filename)
    {
        $filePath = storage_path('app/backups/' . $filename);

        if (!File::exists($filePath)) {
            Toastr::error(translate('Backup file not found!'));
            return redirect()->back();
        }

        return response()->download($filePath);
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup(string $filename): RedirectResponse
    {
        try {
            $filePath = storage_path('app/backups/' . $filename);

            if (File::exists($filePath)) {
                File::delete($filePath);
                Toastr::success(translate('Backup deleted successfully!'));
            } else {
                Toastr::error(translate('Backup file not found!'));
            }
        } catch (\Exception $e) {
            Toastr::error(translate('Failed to delete backup: ') . $e->getMessage());
        }

        return back();
    }

    /**
     * Restore database from backup
     */
    public function restoreBackup(Request $request): RedirectResponse
    {
        $filename = $request->input('filename');
        $filePath = storage_path('app/backups/' . $filename);

        if (!File::exists($filePath)) {
            Toastr::error(translate('Backup file not found!'));
            return back();
        }

        try {
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');
            $port = config('database.connections.mysql.port', 3306);

            // Restore using mysql command
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $command = sprintf(
                    'mysql -h%s -P%s -u%s -p%s %s < "%s"',
                    $host,
                    $port,
                    $username,
                    $password,
                    $database,
                    $filePath
                );
            } else {
                $command = sprintf(
                    'mysql -h%s -P%s -u%s -p%s %s < %s 2>&1',
                    $host,
                    $port,
                    $username,
                    $password,
                    $database,
                    $filePath
                );
            }

            exec($command, $output, $return);

            if ($return !== 0) {
                throw new \Exception('Restore command failed');
            }

            Toastr::success(translate('Database restored successfully!'));
        } catch (\Exception $e) {
            Toastr::error(translate('Restore failed: ') . $e->getMessage());
        }

        return back();
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}