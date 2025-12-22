<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;

class GenerateUserQRCodes extends Command
{
    protected $signature = 'qr:generate-all {--force : Regenerate QR codes for all users}';
    
    protected $description = 'Generate QR codes for users who don\'t have one';

    public function handle()
    {
        $force = $this->option('force');
        
        if ($force) {
            $users = User::all();
            $this->info('Regenerating QR codes for all users...');
        } else {
            $users = User::whereNull('qr_code')
                ->orWhereNull('qr_code_image')
                ->get();
            $this->info('Generating QR codes for users without one...');
        }
        
        $this->info("Found {$users->count()} users to process.");
        
        $bar = $this->output->createProgressBar($users->count());
        $bar->start();
        
        $generated = 0;
        $failed = 0;
        
        foreach ($users as $user) {
            if ($user->generateQRCode()) {
                $generated++;
            } else {
                $failed++;
                $this->newLine();
                $this->error("Failed to generate QR code for user ID: {$user->id}");
            }
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("QR Code Generation Complete!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Generated', $generated],
                ['Failed', $failed],
                ['Total', $users->count()],
            ]
        );
        
        return 0;
    }
}